<?php
// One-time CRM cleanup. See D:\GithubRepos\AGENTS.md for workspace rules.
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);

define('BASEPATH', __DIR__ . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', __DIR__ . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/app-config.php';
require_once APPPATH . 'third_party/phpass.php';

$dbPrefix = defined('APP_DB_PREFIX') ? APP_DB_PREFIX : 'tbl';
$db = @new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($db->connect_errno) {
    $db = @new mysqli('127.0.0.1', APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
}
if ($db->connect_errno) {
    fwrite(STDERR, 'Database connection failed: ' . $db->connect_error . PHP_EOL);
    exit(1);
}

$db->set_charset(APP_DB_CHARSET ?: 'utf8mb4');

$clientsTable = $dbPrefix . 'clients';
$contactsTable = $dbPrefix . 'contacts';
$customFieldsTable = $dbPrefix . 'customfields';
$customFieldValuesTable = $dbPrefix . 'customfieldsvalues';
$permissionsTable = $dbPrefix . 'contact_permissions';
$hasher = new PasswordHash(8, true);
$now = date('Y-m-d H:i:s');

function fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function normalize_email(?string $email): ?string
{
    $email = strtolower(trim((string) $email));
    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    return $email;
}

function fetch_columns(mysqli $db, string $table): array
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

function split_name(string $company): array
{
    $company = trim($company);
    if ($company === '') {
        return ['Client', 'Contact'];
    }

    $parts = preg_split('/\s+/', $company);
    $first = $parts[0] ?? 'Client';
    $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'Contact';

    return [$first, $last];
}

function ensure_permissions(mysqli $db, string $table, int $contactId, array $permissionIds): int
{
    $added = 0;
    foreach ($permissionIds as $permissionId) {
        $checkStmt = $db->prepare('SELECT id FROM `' . $table . '` WHERE userid = ? AND permission_id = ? LIMIT 1');
        if (! $checkStmt) {
            fail('Permission check prepare failed: ' . $db->error);
        }
        $checkStmt->bind_param('ii', $contactId, $permissionId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $exists = $result ? $result->fetch_assoc() : null;
        $checkStmt->close();

        if ($exists) {
            continue;
        }

        $insertStmt = $db->prepare('INSERT INTO `' . $table . '` (userid, permission_id) VALUES (?, ?)');
        if (! $insertStmt) {
            fail('Permission insert prepare failed: ' . $db->error);
        }
        $insertStmt->bind_param('ii', $contactId, $permissionId);
        if (! $insertStmt->execute()) {
            fail('Permission insert failed: ' . $insertStmt->error);
        }
        $insertStmt->close();
        $added++;
    }

    return $added;
}

$contactCols = fetch_columns($db, $contactsTable);
$contactPermissionIds = [1, 2, 3, 4, 5, 6];

$altFieldStmt = $db->prepare(
    'SELECT id, name FROM `' . $customFieldsTable . '`
     WHERE fieldto = "customers"
       AND (
            LOWER(name) IN ("alternate email", "alternate email address", "secondary email", "email 2", "email2")
            OR (LOWER(name) LIKE "%alternate%" AND LOWER(name) LIKE "%email%")
            OR (LOWER(name) LIKE "%secondary%" AND LOWER(name) LIKE "%email%")
       )'
);
if (! $altFieldStmt) {
    fail('Alternate-email custom field prepare failed: ' . $db->error);
}
$altFieldStmt->execute();
$altFieldResult = $altFieldStmt->get_result();
$altFieldIds = [];
while ($row = $altFieldResult->fetch_assoc()) {
    $altFieldIds[] = (int) $row['id'];
}
$altFieldStmt->close();

if ($altFieldIds === []) {
    echo json_encode([
        'status' => 'ok',
        'message' => 'No alternate-email customer custom fields found.',
        'stats' => [
            'clients_scanned' => 0,
            'emails_promoted' => 0,
            'contacts_created' => 0,
            'contacts_updated' => 0,
            'permissions_added' => 0,
        ],
    ], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

$fieldPlaceholders = implode(',', array_fill(0, count($altFieldIds), '?'));
$fieldTypes = str_repeat('i', count($altFieldIds));
$sql = '
SELECT c.userid, c.company, c.phonenumber, cfv.value AS alternate_email, pc.id AS primary_contact_id, pc.email AS primary_email
FROM `' . $clientsTable . '` c
INNER JOIN `' . $customFieldValuesTable . '` cfv
    ON cfv.relid = c.userid AND cfv.fieldto = "customers" AND cfv.fieldid IN (' . $fieldPlaceholders . ')
LEFT JOIN `' . $contactsTable . '` pc
    ON pc.userid = c.userid AND pc.is_primary = 1
WHERE TRIM(IFNULL(cfv.value, "")) <> ""
ORDER BY c.userid ASC';

$stmt = $db->prepare($sql);
if (! $stmt) {
    fail('Candidate query prepare failed: ' . $db->error);
}
$stmt->bind_param($fieldTypes, ...$altFieldIds);
$stmt->execute();
$result = $stmt->get_result();

$stats = [
    'clients_scanned' => 0,
    'emails_promoted' => 0,
    'contacts_created' => 0,
    'contacts_updated' => 0,
    'permissions_added' => 0,
    'skipped_invalid_alt_email' => 0,
    'skipped_existing_primary' => 0,
];

while ($row = $result->fetch_assoc()) {
    $stats['clients_scanned']++;
    $alternateEmail = normalize_email($row['alternate_email'] ?? '');
    if (! $alternateEmail) {
        $stats['skipped_invalid_alt_email']++;
        continue;
    }

    $primaryEmail = normalize_email($row['primary_email'] ?? '');
    if ($primaryEmail) {
        $stats['skipped_existing_primary']++;
        continue;
    }

    $emailCheckStmt = $db->prepare('SELECT id, userid FROM `' . $contactsTable . '` WHERE email = ? LIMIT 1');
    if (! $emailCheckStmt) {
        fail('Email check prepare failed: ' . $db->error);
    }
    $emailCheckStmt->bind_param('s', $alternateEmail);
    $emailCheckStmt->execute();
    $emailCheckResult = $emailCheckStmt->get_result();
    $existingEmailOwner = $emailCheckResult ? $emailCheckResult->fetch_assoc() : null;
    $emailCheckStmt->close();

    if ($existingEmailOwner && (int) $existingEmailOwner['userid'] !== (int) $row['userid']) {
        continue;
    }

    $db->begin_transaction();
    try {
        if (! empty($row['primary_contact_id'])) {
            $updateParts = ['email = ?'];
            $params = [$alternateEmail];
            $types = 's';

            foreach (['active', 'is_primary'] as $flagColumn) {
                if (isset($contactCols[$flagColumn])) {
                    $updateParts[] = $flagColumn . ' = 1';
                }
            }
            foreach (['invoice_emails', 'estimate_emails', 'credit_note_emails', 'contract_emails', 'task_emails', 'project_emails', 'ticket_emails'] as $notifyColumn) {
                if (isset($contactCols[$notifyColumn])) {
                    $updateParts[] = $notifyColumn . ' = 1';
                }
            }
            if (isset($contactCols['email_verified_at'])) {
                $updateParts[] = 'email_verified_at = ?';
                $params[] = $now;
                $types .= 's';
            }
            if (isset($contactCols['direction'])) {
                $updateParts[] = 'direction = CASE WHEN TRIM(IFNULL(direction, "")) = "" THEN "ltr" ELSE direction END';
            }

            $params[] = (int) $row['primary_contact_id'];
            $types .= 'i';
            $updateSql = 'UPDATE `' . $contactsTable . '` SET ' . implode(', ', $updateParts) . ' WHERE id = ?';
            $updateStmt = $db->prepare($updateSql);
            if (! $updateStmt) {
                fail('Primary contact update prepare failed: ' . $db->error);
            }
            $updateStmt->bind_param($types, ...$params);
            if (! $updateStmt->execute()) {
                fail('Primary contact update failed: ' . $updateStmt->error);
            }
            $updateStmt->close();

            $stats['contacts_updated']++;
            $stats['permissions_added'] += ensure_permissions($db, $permissionsTable, (int) $row['primary_contact_id'], $contactPermissionIds);
        } else {
            [$firstName, $lastName] = split_name((string) ($row['company'] ?? ''));
            $passwordPlain = bin2hex(random_bytes(8)) . 'Aa1!';
            $passwordHash = $hasher->HashPassword($passwordPlain);

            $contactData = [];
            foreach ([
                'userid' => (int) $row['userid'],
                'is_primary' => 1,
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email' => $alternateEmail,
                'phonenumber' => trim((string) ($row['phonenumber'] ?? '')),
                'password' => $passwordHash,
                'datecreated' => $now,
                'active' => 1,
                'email_verified_at' => $now,
                'invoice_emails' => 1,
                'estimate_emails' => 1,
                'credit_note_emails' => 1,
                'contract_emails' => 1,
                'task_emails' => 1,
                'project_emails' => 1,
                'ticket_emails' => 1,
                'direction' => 'ltr',
            ] as $column => $value) {
                if (isset($contactCols[$column])) {
                    $contactData[$column] = $value;
                }
            }

            $fields = array_keys($contactData);
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $types = '';
            $params = [];
            foreach ($contactData as $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
                $params[] = $value;
            }

            $insertSql = 'INSERT INTO `' . $contactsTable . '` (`' . implode('`,`', $fields) . '`) VALUES (' . $placeholders . ')';
            $insertStmt = $db->prepare($insertSql);
            if (! $insertStmt) {
                fail('Primary contact insert prepare failed: ' . $db->error);
            }
            $insertStmt->bind_param($types, ...$params);
            if (! $insertStmt->execute()) {
                fail('Primary contact insert failed: ' . $insertStmt->error);
            }
            $newContactId = (int) $db->insert_id;
            $insertStmt->close();

            $stats['contacts_created']++;
            $stats['permissions_added'] += ensure_permissions($db, $permissionsTable, $newContactId, $contactPermissionIds);
        }

        $db->commit();
        $stats['emails_promoted']++;
    } catch (Throwable $e) {
        $db->rollback();
        fail('Cleanup failed for client ' . $row['userid'] . ': ' . $e->getMessage());
    }
}

$stmt->close();

echo json_encode([
    'status' => 'ok',
    'stats' => $stats,
], JSON_PRETTY_PRINT) . PHP_EOL;
