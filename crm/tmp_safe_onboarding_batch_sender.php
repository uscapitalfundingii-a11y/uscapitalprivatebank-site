<?php

/**
 * Controlled onboarding batch sender.
 * Future agents: read D:\GithubRepos\AGENTS.md before changing this workflow.
 *
 * This script is intentionally stricter than tmp_bulk_onboarding_sender.php:
 * - requires a non-empty allow-list path
 * - requires an explicit suppression/unsubscribe file path
 * - requires a positive batch limit
 * - skips emails already present in onboarding tickets
 * - skips emails already present in the mail queue
 * - defaults to dry-run unless --execute is supplied
 * - logs aggregate counts only; it never prints recipient lists
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);

define('BASEPATH', __DIR__ . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', __DIR__ . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/app-config.php';

$dbPrefix = defined('APP_DB_PREFIX') ? APP_DB_PREFIX : 'tbl';
$subject = 'Onboarding Invitation - US Capital Private Bank, ETO';
$departmentName = 'Customer Service';
$statusName = 'Staff Initiated';
$predefinedReplyId = 94;

function fail_safe(string $message, int $code = 1): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

function parseArgs(array $argv): array
{
    $args = [
        'allow-list' => '',
        'suppression' => '',
        'limit' => 0,
        'log' => '',
        'execute' => false,
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--execute') {
            $args['execute'] = true;
            continue;
        }

        if (strpos($arg, '--') !== 0 || strpos($arg, '=') === false) {
            fail_safe('Unknown argument: ' . $arg);
        }

        [$key, $value] = explode('=', substr($arg, 2), 2);
        if (! array_key_exists($key, $args)) {
            fail_safe('Unknown argument: --' . $key);
        }

        $args[$key] = $value;
    }

    $args['limit'] = (int) $args['limit'];
    if ($args['allow-list'] === '') {
        fail_safe('Missing required --allow-list path.');
    }
    if ($args['suppression'] === '') {
        fail_safe('Missing required --suppression path.');
    }
    if ($args['limit'] < 1 || $args['limit'] > 500) {
        fail_safe('Batch --limit must be between 1 and 500.');
    }
    if ($args['log'] === '') {
        $args['log'] = __DIR__ . DIRECTORY_SEPARATOR . 'tmp_safe_onboarding_batch_sender.log';
    }

    return $args;
}

function normalizeEmail($email): ?string
{
    $email = strtolower(trim((string) $email));
    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    return $email;
}

function loadEmailSet(string $path, string $label): array
{
    if (! is_file($path)) {
        fail_safe($label . ' file not found: ' . $path);
    }

    $raw = (string) file_get_contents($path);
    $emails = [];
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $source = $json['emails'] ?? $json;
        if (! is_array($source)) {
            fail_safe($label . ' JSON does not contain an emails array: ' . $path);
        }
        foreach ($source as $email) {
            $normalized = normalizeEmail($email);
            if ($normalized !== null) {
                $emails[$normalized] = true;
            }
        }
        return $emails;
    }

    foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
        foreach (str_getcsv($line) as $field) {
            $normalized = normalizeEmail($field);
            if ($normalized !== null) {
                $emails[$normalized] = true;
            }
        }
    }

    return $emails;
}

function fetchAssoc(mysqli $db, string $sql, array $params = []): ?array
{
    $stmt = $db->prepare($sql);
    if (! $stmt) {
        fail_safe('Prepare failed: ' . $db->error);
    }

    if ($params !== []) {
        $types = '';
        $bind = [];
        foreach ($params as $param) {
            $types .= is_int($param) ? 'i' : 's';
            $bind[] = $param;
        }
        $stmt->bind_param($types, ...$bind);
    }

    if (! $stmt->execute()) {
        fail_safe('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function fetchColumns(mysqli $db, string $table): array
{
    $columns = [];
    $result = $db->query('SHOW COLUMNS FROM `' . $table . '`');
    if (! $result) {
        fail_safe('Could not inspect table ' . $table . ': ' . $db->error);
    }
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = true;
    }

    return $columns;
}

function fetchEmailSet(mysqli $db, string $sql, array $params = []): array
{
    $stmt = $db->prepare($sql);
    if (! $stmt) {
        fail_safe('Prepare failed: ' . $db->error);
    }
    if ($params !== []) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    if (! $stmt->execute()) {
        fail_safe('Execute failed: ' . $stmt->error);
    }
    $result = $stmt->get_result();
    $emails = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $email = normalizeEmail($row['email'] ?? '');
        if ($email !== null) {
            $emails[$email] = true;
        }
    }
    $stmt->close();

    return $emails;
}

function insertRow(mysqli $db, string $table, array $row): int
{
    $fields = array_keys($row);
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $fields) . '`) VALUES (' . $placeholders . ')';
    $stmt = $db->prepare($sql);
    if (! $stmt) {
        fail_safe('Prepare insert failed for ' . $table . ': ' . $db->error);
    }

    $types = '';
    $bind = [];
    foreach ($row as $value) {
        $types .= is_int($value) ? 'i' : 's';
        $bind[] = $value;
    }
    $stmt->bind_param($types, ...$bind);
    if (! $stmt->execute()) {
        fail_safe('Insert failed for ' . $table . ': ' . $stmt->error);
    }
    $insertId = $db->insert_id;
    $stmt->close();

    return $insertId;
}

$args = parseArgs($argv);
$allowList = loadEmailSet($args['allow-list'], 'Allow-list');
$suppression = loadEmailSet($args['suppression'], 'Suppression');
if ($allowList === []) {
    fail_safe('Allow-list is empty; refusing all-contacts mode.');
}

$db = @new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($db->connect_errno) {
    $db = @new mysqli('127.0.0.1', APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
}
if ($db->connect_errno) {
    fail_safe('Database connection failed: ' . $db->connect_error);
}
$db->set_charset(APP_DB_CHARSET ?: 'utf8mb4');

$department = fetchAssoc($db, 'SELECT departmentid, email FROM `' . $dbPrefix . 'departments` WHERE name = ? LIMIT 1', [$departmentName]);
$status = fetchAssoc($db, 'SELECT ticketstatusid, name FROM `' . $dbPrefix . 'tickets_status` WHERE LOWER(REPLACE(name, "-", " ")) = ? LIMIT 1', [strtolower(str_replace('-', ' ', $statusName))]);
$predefined = fetchAssoc($db, 'SELECT id, name, message FROM `' . $dbPrefix . 'tickets_predefined_replies` WHERE id = ? LIMIT 1', [$predefinedReplyId]);
$openedByRow = fetchAssoc($db, 'SELECT staffid FROM `' . $dbPrefix . 'staff` WHERE active = 1 ORDER BY admin DESC, staffid ASC LIMIT 1');
if (! $department || ! $status || ! $predefined || ! $openedByRow) {
    fail_safe('Missing department, status, predefined reply, or staff owner.');
}

$mailEngineOption = fetchAssoc($db, 'SELECT value FROM `' . $dbPrefix . 'options` WHERE name = ? LIMIT 1', ['mail_engine']);
$smtpEmailOption = fetchAssoc($db, 'SELECT value FROM `' . $dbPrefix . 'options` WHERE name = ? LIMIT 1', ['smtp_email']);
$companyNameOption = fetchAssoc($db, 'SELECT value FROM `' . $dbPrefix . 'options` WHERE name = ? LIMIT 1', ['companyname']);
$mailEngine = trim((string) ($mailEngineOption['value'] ?? 'phpmailer')) ?: 'phpmailer';
$smtpEmail = trim((string) ($smtpEmailOption['value'] ?? ''));
$companyName = trim((string) ($companyNameOption['value'] ?? 'U.S. Capital Private Bank, ETO'));

$ticketColumns = fetchColumns($db, $dbPrefix . 'tickets');
$queueColumns = fetchColumns($db, $dbPrefix . 'mail_queue');
$alreadyQueued = fetchEmailSet($db, 'SELECT DISTINCT email FROM `' . $dbPrefix . 'mail_queue` WHERE email IS NOT NULL AND email != ""');
$alreadyTicketed = fetchEmailSet($db, 'SELECT DISTINCT email FROM `' . $dbPrefix . 'tickets` WHERE subject = ? AND email IS NOT NULL AND email != ""', [$subject]);

$now = date('Y-m-d H:i:s');
$message = (string) $predefined['message'];
$altMessage = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message)));
$attachments = base64_encode(serialize([]));
$headers = serialize([
    'from' => $smtpEmail,
    'fromName' => $companyName,
    'subject' => $subject,
]);

$stats = [
    'mode' => $args['execute'] ? 'execute' : 'dry-run',
    'batch_limit' => $args['limit'],
    'allow_list_count' => count($allowList),
    'suppression_count' => count($suppression),
    'attempted' => 0,
    'created_tickets' => 0,
    'queued_emails' => 0,
    'skipped_suppressed' => 0,
    'skipped_existing_mail_queue' => 0,
    'skipped_existing_onboarding_ticket' => 0,
    'skipped_missing_contact' => 0,
    'failures' => 0,
];

foreach (array_keys($allowList) as $email) {
    if ($stats['attempted'] >= $args['limit']) {
        break;
    }
    if (isset($suppression[$email])) {
        $stats['skipped_suppressed']++;
        continue;
    }
    if (isset($alreadyQueued[$email])) {
        $stats['skipped_existing_mail_queue']++;
        continue;
    }
    if (isset($alreadyTicketed[$email])) {
        $stats['skipped_existing_onboarding_ticket']++;
        continue;
    }

    $contact = fetchAssoc($db, 'SELECT id, userid, firstname, lastname, email FROM `' . $dbPrefix . 'contacts` WHERE LOWER(email) = ? LIMIT 1', [$email]);
    if (! $contact) {
        $stats['skipped_missing_contact']++;
        continue;
    }

    $name = trim(($contact['firstname'] ?? '') . ' ' . ($contact['lastname'] ?? ''));
    if ($name === '') {
        $name = $email;
    }

    $ticketCandidate = [
        'admin' => (int) $openedByRow['staffid'],
        'userid' => (int) ($contact['userid'] ?? 0),
        'contactid' => (int) ($contact['id'] ?? 0),
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'department' => (int) $department['departmentid'],
        'priority' => 0,
        'status' => (int) $status['ticketstatusid'],
        'date' => $now,
        'lastreply' => $now,
        'service' => 0,
        'project_id' => 0,
        'assigned' => 0,
        'ticketkey' => md5(uniqid($email, true)),
        'adminread' => 1,
        'clientread' => 0,
    ];
    $queueCandidate = [
        'engine' => $mailEngine,
        'email' => $email,
        'cc' => '',
        'bcc' => '',
        'message' => $message,
        'alt_message' => $altMessage,
        'headers' => $headers,
        'attachments' => $attachments,
        'status' => 'pending',
        'date' => $now,
    ];

    $stats['attempted']++;
    if ($args['execute']) {
        insertRow($db, $dbPrefix . 'tickets', array_intersect_key($ticketCandidate, $ticketColumns));
        insertRow($db, $dbPrefix . 'mail_queue', array_intersect_key($queueCandidate, $queueColumns));
        $stats['created_tickets']++;
        $stats['queued_emails']++;
    }
}

$stats['finished_at'] = date('c');
$encoded = json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($args['log'], $encoded . PHP_EOL, FILE_APPEND);
echo $encoded . PHP_EOL;
