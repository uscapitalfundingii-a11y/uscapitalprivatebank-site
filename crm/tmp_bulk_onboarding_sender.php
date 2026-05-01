<?php

/**
 * One-time operational sender for onboarding invitation tickets/emails.
 * Future agents: read D:\GithubRepos\AGENTS.md before changing this workflow.
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

function fail(string $message): void
{
    echo $message . PHP_EOL;
    exit(1);
}

function fetchAssoc(mysqli $db, string $sql, array $params = []): ?array
{
    $stmt = $db->prepare($sql);
    if (! $stmt) {
        fail('Prepare failed: ' . $db->error);
    }

    if ($params !== []) {
        $types = '';
        $bind = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
            $bind[] = $param;
        }
        $stmt->bind_param($types, ...$bind);
    }

    if (! $stmt->execute()) {
        fail('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function fetchAll(mysqli $db, string $sql, array $params = []): array
{
    $stmt = $db->prepare($sql);
    if (! $stmt) {
        fail('Prepare failed: ' . $db->error);
    }

    if ($params !== []) {
        $types = '';
        $bind = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
            $bind[] = $param;
        }
        $stmt->bind_param($types, ...$bind);
    }

    if (! $stmt->execute()) {
        fail('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $rows;
}

function fetchColumns(mysqli $db, string $table): array
{
    $columns = [];
    $result = $db->query('SHOW COLUMNS FROM `' . $table . '`');
    if (! $result) {
        fail('Could not inspect table ' . $table . ': ' . $db->error);
    }

    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    return $columns;
}

function insertRow(mysqli $db, string $table, array $row): int
{
    $fields = array_keys($row);
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $fields) . '`) VALUES (' . $placeholders . ')';

    $stmt = $db->prepare($sql);
    if (! $stmt) {
        fail('Prepare insert failed for ' . $table . ': ' . $db->error);
    }

    $types = '';
    $bind = [];
    foreach ($row as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } else {
            $types .= 's';
        }
        $bind[] = $value;
    }

    $stmt->bind_param($types, ...$bind);

    if (! $stmt->execute()) {
        fail('Insert failed for ' . $table . ': ' . $stmt->error);
    }

    $insertId = $db->insert_id;
    $stmt->close();

    return $insertId;
}

$db = @new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($db->connect_errno) {
    $db = @new mysqli('127.0.0.1', APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
}

if ($db->connect_errno) {
    fail('Database connection failed: ' . $db->connect_error);
}

$db->set_charset(APP_DB_CHARSET ?: 'utf8mb4');

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

$mailEngineOption = fetchAssoc(
    $db,
    'SELECT value FROM `' . $dbPrefix . 'options` WHERE name = ? LIMIT 1',
    ['mail_engine']
);
$smtpEmailOption = fetchAssoc(
    $db,
    'SELECT value FROM `' . $dbPrefix . 'options` WHERE name = ? LIMIT 1',
    ['smtp_email']
);
$companyNameOption = fetchAssoc(
    $db,
    'SELECT value FROM `' . $dbPrefix . 'options` WHERE name = ? LIMIT 1',
    ['companyname']
);

$mailEngine = trim((string) ($mailEngineOption['value'] ?? 'phpmailer')) ?: 'phpmailer';
$smtpEmail = trim((string) ($smtpEmailOption['value'] ?? ''));
$companyName = trim((string) ($companyNameOption['value'] ?? 'U.S. Capital Private Bank, ETO'));

$ticketColumns = array_flip(fetchColumns($db, $dbPrefix . 'tickets'));
$queueColumns = array_flip(fetchColumns($db, $dbPrefix . 'mail_queue'));

$contacts = fetchAll(
    $db,
    'SELECT id, userid, firstname, lastname, email FROM `' . $dbPrefix . 'contacts` WHERE email IS NOT NULL AND email != ""'
);

$staff = fetchAll(
    $db,
    'SELECT staffid, firstname, lastname, email FROM `' . $dbPrefix . 'staff` WHERE active = 1 AND email IS NOT NULL AND email != ""'
);

$openedByRow = fetchAssoc(
    $db,
    'SELECT staffid FROM `' . $dbPrefix . 'staff` WHERE active = 1 ORDER BY admin DESC, staffid ASC LIMIT 1'
);
if (! $openedByRow) {
    fail('No active staff found to open these tickets.');
}

$openedBy = (int) $openedByRow['staffid'];
$now = date('Y-m-d H:i:s');
$message = (string) $predefined['message'];
$altMessage = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message)));
$attachments = base64_encode(serialize([]));
$headers = serialize([
    'from'     => $smtpEmail,
    'fromName' => $companyName,
    'subject'  => $subject,
]);

$seenEmails = [];
$createdTickets = 0;
$queuedEmails = 0;
$skippedDuplicates = 0;

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

$processRecipient = function (array $recipient) use (
    &$db,
    &$seenEmails,
    &$createdTickets,
    &$queuedEmails,
    &$skippedDuplicates,
    $dbPrefix,
    $makeTicketRow,
    $makeQueueRow
): void {
    $email = strtolower(trim((string) $recipient['email']));
    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    if (isset($seenEmails[$email])) {
        $skippedDuplicates++;
        return;
    }

    $seenEmails[$email] = true;

    insertRow($db, $dbPrefix . 'tickets', $makeTicketRow($recipient));
    $createdTickets++;

    insertRow($db, $dbPrefix . 'mail_queue', $makeQueueRow($email));
    $queuedEmails++;
};

foreach ($contacts as $contact) {
    $name = trim(($contact['firstname'] ?? '') . ' ' . ($contact['lastname'] ?? ''));
    if ($name === '') {
        $name = trim((string) $contact['email']);
    }

    $processRecipient([
        'userid'    => (int) ($contact['userid'] ?? 0),
        'contactid' => (int) ($contact['id'] ?? 0),
        'name'      => $name,
        'email'     => (string) $contact['email'],
    ]);
}

foreach ($staff as $member) {
    $name = trim(($member['firstname'] ?? '') . ' ' . ($member['lastname'] ?? ''));
    if ($name === '') {
        $name = trim((string) $member['email']);
    }

    $processRecipient([
        'userid'    => 0,
        'contactid' => 0,
        'name'      => $name,
        'email'     => (string) $member['email'],
    ]);
}

echo "Bulk onboarding invitation complete.\n";
echo 'Status used: ' . $status['name'] . ' (#' . $status['ticketstatusid'] . ')' . "\n";
echo 'Department used: ' . $departmentName . ' (#' . $department['departmentid'] . ')' . "\n";
echo 'Created tickets: ' . $createdTickets . "\n";
echo 'Queued emails: ' . $queuedEmails . "\n";
echo 'Skipped duplicate emails: ' . $skippedDuplicates . "\n";
