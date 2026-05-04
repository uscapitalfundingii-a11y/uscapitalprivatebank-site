<?php

/**
 * Safe DreamHost onboarding email queue batch.
 * Future agents: read D:\GithubRepos\AGENTS.md before changing this workflow.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);

define('BASEPATH', __DIR__ . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', __DIR__ . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/app-config.php';

$subject = 'Onboarding Invitation - US Capital Private Bank, ETO';
$departmentName = 'Customer Service';
$statusName = 'Staff Initiated';
$predefinedReplyId = 94;
$limit = 1000;
$allowListPath = '';
$dryRun = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(0, (int) substr($arg, 8));
    } elseif (str_starts_with($arg, '--allow=')) {
        $allowListPath = substr($arg, 8);
    } elseif ($arg === '--dry-run') {
        $dryRun = true;
    } elseif ($allowListPath === '') {
        $allowListPath = $arg;
    }
}

function fail(string $message): void
{
    echo 'ERROR: ' . $message . PHP_EOL;
    exit(1);
}

function connectDb(): mysqli
{
    $db = @new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
    if ($db->connect_errno) {
        $db = @new mysqli('127.0.0.1', APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
    }

    if ($db->connect_errno) {
        fail('Database connection failed: ' . $db->connect_error);
    }

    $db->set_charset(APP_DB_CHARSET ?: 'utf8mb4');
    return $db;
}

function fetchAssoc(mysqli $db, string $sql, array $params = []): ?array
{
    $stmt = $db->prepare($sql);
    if (! $stmt) {
        fail('Prepare failed: ' . $db->error);
    }

    if ($params !== []) {
        $types = '';
        foreach ($params as $param) {
            $types .= is_int($param) ? 'i' : 's';
        }
        $stmt->bind_param($types, ...$params);
    }

    if (! $stmt->execute()) {
        fail('Execute failed: ' . $stmt->error);
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
        fail('Could not inspect table ' . $table . ': ' . $db->error);
    }

    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = true;
    }

    return $columns;
}

function insertRow(mysqli $db, string $table, array $row, bool $dryRun): int
{
    if ($dryRun) {
        return 0;
    }

    $fields = array_keys($row);
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $fields) . '`) VALUES (' . $placeholders . ')';

    $stmt = $db->prepare($sql);
    if (! $stmt) {
        fail('Prepare insert failed for ' . $table . ': ' . $db->error);
    }

    $types = '';
    $values = [];
    foreach ($row as $value) {
        $types .= is_int($value) ? 'i' : 's';
        $values[] = $value;
    }

    $stmt->bind_param($types, ...$values);
    if (! $stmt->execute()) {
        fail('Insert failed for ' . $table . ': ' . $stmt->error);
    }

    $insertId = $db->insert_id;
    $stmt->close();

    return $insertId;
}

function loadAllowList(string $path): array
{
    if ($path === '') {
        fail('Allow-list path is required.');
    }

    if (! is_file($path)) {
        fail('Allow-list file not found: ' . $path);
    }

    $json = json_decode((string) file_get_contents($path), true);
    if (! is_array($json)) {
        fail('Allow-list JSON is invalid: ' . $path);
    }

    $emails = $json['emails'] ?? $json;
    if (! is_array($emails)) {
        fail('Allow-list JSON does not contain an email array.');
    }

    $allow = [];
    foreach ($emails as $email) {
        $email = strtolower(trim((string) $email));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $allow[$email] = true;
        }
    }

    return $allow;
}

if ($limit < 1) {
    fail('Limit must be at least 1.');
}

$allowList = loadAllowList($allowListPath);
if ($allowList === []) {
    fail('Allow-list is empty. Refusing all-contacts mode.');
}

$db = connectDb();
$readDb = connectDb();
$dbPrefix = defined('APP_DB_PREFIX') ? APP_DB_PREFIX : 'tbl';

$department = fetchAssoc(
    $db,
    'SELECT departmentid, email FROM `' . $dbPrefix . 'departments` WHERE name = ? LIMIT 1',
    [$departmentName]
);
if (! $department) {
    fail('Department not found: ' . $departmentName);
}

$status = fetchAssoc(
    $db,
    'SELECT ticketstatusid, name FROM `' . $dbPrefix . 'tickets_status` WHERE LOWER(REPLACE(name, "-", " ")) = ? LIMIT 1',
    [strtolower(str_replace('-', ' ', $statusName))]
);
if (! $status) {
    fail('Ticket status not found: ' . $statusName);
}

$predefined = fetchAssoc(
    $db,
    'SELECT id, name, message FROM `' . $dbPrefix . 'tickets_predefined_replies` WHERE id = ? LIMIT 1',
    [$predefinedReplyId]
);
if (! $predefined || trim((string) $predefined['message']) === '') {
    fail('Predefined reply not found or empty: ' . $predefinedReplyId);
}

$openedByRow = fetchAssoc(
    $db,
    'SELECT staffid FROM `' . $dbPrefix . 'staff` WHERE active = 1 ORDER BY admin DESC, staffid ASC LIMIT 1'
);
if (! $openedByRow) {
    fail('No active staff found to open tickets.');
}

$mailEngineOption = fetchAssoc($db, 'SELECT value FROM `' . $dbPrefix . 'options` WHERE name = ? LIMIT 1', ['mail_engine']);
$smtpEmailOption = fetchAssoc($db, 'SELECT value FROM `' . $dbPrefix . 'options` WHERE name = ? LIMIT 1', ['smtp_email']);
$companyNameOption = fetchAssoc($db, 'SELECT value FROM `' . $dbPrefix . 'options` WHERE name = ? LIMIT 1', ['companyname']);

$mailEngine = trim((string) ($mailEngineOption['value'] ?? 'phpmailer')) ?: 'phpmailer';
$smtpEmail = trim((string) ($smtpEmailOption['value'] ?? ''));
$companyName = trim((string) ($companyNameOption['value'] ?? 'U.S. Capital Private Bank, ETO'));
$openedBy = (int) $openedByRow['staffid'];
$ticketColumns = fetchColumns($db, $dbPrefix . 'tickets');
$queueColumns = fetchColumns($db, $dbPrefix . 'mail_queue');
$now = date('Y-m-d H:i:s');
$message = (string) $predefined['message'];
$altMessage = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message)));
$headers = serialize([
    'from'     => $smtpEmail,
    'fromName' => $companyName,
    'subject'  => $subject,
]);
$attachments = base64_encode(serialize([]));
$mailHeaderNeedle = '%' . $subject . '%';

$ticketExistsStmt = $db->prepare('SELECT ticketid FROM `' . $dbPrefix . 'tickets` WHERE email = ? AND subject = ? LIMIT 1');
$queueExistsStmt = $db->prepare('SELECT id FROM `' . $dbPrefix . 'mail_queue` WHERE email = ? AND headers LIKE ? LIMIT 1');
if (! $ticketExistsStmt || ! $queueExistsStmt) {
    fail('Could not prepare duplicate checks: ' . $db->error);
}

$stats = [
    'allow_list' => count($allowList),
    'limit' => $limit,
    'scanned_contacts' => 0,
    'scanned_staff' => 0,
    'disallowed' => 0,
    'invalid' => 0,
    'skipped_duplicate_in_run' => 0,
    'skipped_existing_ticket' => 0,
    'skipped_existing_queue' => 0,
    'created_tickets' => 0,
    'queued_emails' => 0,
    'dry_run' => $dryRun ? 1 : 0,
];

$seen = [];

$makeTicketRow = function (array $base) use ($ticketColumns, $department, $status, $openedBy, $subject, $message, $now): array {
    $candidate = [
        'admin'      => $openedBy,
        'userid'     => (int) ($base['userid'] ?? 0),
        'contactid'  => (int) ($base['contactid'] ?? 0),
        'name'       => $base['name'],
        'email'      => $base['email'],
        'subject'    => $subject,
        'message'    => $message,
        'department' => (int) $department['departmentid'],
        'priority'   => 0,
        'status'     => (int) $status['ticketstatusid'],
        'date'       => $now,
        'lastreply'  => $now,
        'service'    => 0,
        'project_id' => 0,
        'assigned'   => 0,
        'ticketkey'  => md5(uniqid($base['email'], true)),
        'adminread'  => 1,
        'clientread' => 0,
    ];

    return array_intersect_key($candidate, $ticketColumns);
};

$makeQueueRow = function (string $email) use ($queueColumns, $mailEngine, $message, $altMessage, $headers, $attachments, $now): array {
    $candidate = [
        'engine'      => $mailEngine,
        'email'       => $email,
        'cc'          => '',
        'bcc'         => '',
        'message'     => $message,
        'alt_message' => $altMessage,
        'headers'     => $headers,
        'attachments' => $attachments,
        'status'      => 'pending',
        'date'        => $now,
    ];

    return array_intersect_key($candidate, $queueColumns);
};

$processRecipient = function (array $recipient, string $source) use (
    &$db,
    &$seen,
    &$stats,
    $allowList,
    $subject,
    $mailHeaderNeedle,
    $ticketExistsStmt,
    $queueExistsStmt,
    $dbPrefix,
    $makeTicketRow,
    $makeQueueRow,
    $dryRun
): void {
    if ($stats['queued_emails'] >= $stats['limit']) {
        return;
    }

    if ($source === 'contact') {
        $stats['scanned_contacts']++;
    } else {
        $stats['scanned_staff']++;
    }

    $email = strtolower(trim((string) $recipient['email']));
    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stats['invalid']++;
        return;
    }

    if (! isset($allowList[$email])) {
        $stats['disallowed']++;
        return;
    }

    if (isset($seen[$email])) {
        $stats['skipped_duplicate_in_run']++;
        return;
    }
    $seen[$email] = true;

    $ticketExistsStmt->bind_param('ss', $email, $subject);
    $ticketExistsStmt->execute();
    $ticketResult = $ticketExistsStmt->get_result();
    if ($ticketResult && $ticketResult->fetch_assoc()) {
        $stats['skipped_existing_ticket']++;
        return;
    }

    $queueExistsStmt->bind_param('ss', $email, $mailHeaderNeedle);
    $queueExistsStmt->execute();
    $queueResult = $queueExistsStmt->get_result();
    if ($queueResult && $queueResult->fetch_assoc()) {
        $stats['skipped_existing_queue']++;
        return;
    }

    insertRow($db, $dbPrefix . 'tickets', $makeTicketRow($recipient), $dryRun);
    $stats['created_tickets']++;

    insertRow($db, $dbPrefix . 'mail_queue', $makeQueueRow($email), $dryRun);
    $stats['queued_emails']++;
};

$contactResult = $readDb->query(
    'SELECT id, userid, firstname, lastname, email FROM `' . $dbPrefix . 'contacts` WHERE email IS NOT NULL AND email != "" ORDER BY id ASC',
    MYSQLI_USE_RESULT
);
if (! $contactResult) {
    fail('Could not stream contacts: ' . $readDb->error);
}

while ($stats['queued_emails'] < $limit && ($contact = $contactResult->fetch_assoc())) {
    $name = trim(($contact['firstname'] ?? '') . ' ' . ($contact['lastname'] ?? ''));
    if ($name === '') {
        $name = trim((string) $contact['email']);
    }

    $processRecipient([
        'userid'    => (int) ($contact['userid'] ?? 0),
        'contactid' => (int) ($contact['id'] ?? 0),
        'name'      => $name,
        'email'     => (string) $contact['email'],
    ], 'contact');
}
$contactResult->close();

if ($stats['queued_emails'] < $limit) {
    $staffResult = $db->query('SELECT staffid, firstname, lastname, email FROM `' . $dbPrefix . 'staff` WHERE active = 1 AND email IS NOT NULL AND email != "" ORDER BY staffid ASC');
    if (! $staffResult) {
        fail('Could not read staff: ' . $db->error);
    }

    while ($stats['queued_emails'] < $limit && ($member = $staffResult->fetch_assoc())) {
        $name = trim(($member['firstname'] ?? '') . ' ' . ($member['lastname'] ?? ''));
        if ($name === '') {
            $name = trim((string) $member['email']);
        }

        $processRecipient([
            'userid'    => 0,
            'contactid' => 0,
            'name'      => $name,
            'email'     => (string) $member['email'],
        ], 'staff');
    }
}

echo "Safe onboarding batch complete.\n";
echo 'Subject: ' . $subject . "\n";
foreach ($stats as $key => $value) {
    echo $key . '=' . $value . "\n";
}

