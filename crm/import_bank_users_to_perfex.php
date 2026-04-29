<?php
// One-time DreamHost CRM import helper.
// For workspace rules and deployment context, see D:\GithubRepos\AGENTS.md.

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script is CLI-only.\n");
    exit(1);
}

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once __DIR__ . '/application/config/app-config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$options = getopt('', ['dry-run', 'merge']);
$dryRun = array_key_exists('dry-run', $options);
$mergeExisting = array_key_exists('merge', $options) || !array_key_exists('dry-run', $options);

$db = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
$db->set_charset(APP_DB_CHARSET);

$prefix = detectPerfexPrefix($db);
$clientsTable = $prefix . 'clients';
$contactsTable = $prefix . 'contacts';
$countriesTable = $prefix . 'countries';

$clientColumns = getTableColumns($db, $clientsTable);
$contactColumns = getTableColumns($db, $contactsTable);
$userColumns = getTableColumns($db, 'users');
$countryMap = buildCountryMap($db, $countriesTable);

$sourceUsers = loadSourceUsers($db, $userColumns);
$existingContactsByEmail = loadExistingContactsByEmail($db, $contactsTable);

$stats = [
    'eligible_users' => count($sourceUsers),
    'created_clients' => 0,
    'created_contacts' => 0,
    'updated_clients' => 0,
    'updated_contacts' => 0,
    'skipped_existing' => 0,
    'failed' => 0,
];

$failures = [];

foreach ($sourceUsers as $user) {
    $email = normalizeEmail($user['email'] ?? '');
    if ($email === '') {
        $stats['failed']++;
        $failures[] = ['email' => '', 'reason' => 'Missing email'];
        continue;
    }

    try {
        $payload = buildPerfexPayload($user, $clientColumns, $contactColumns, $countryMap);

        if (isset($existingContactsByEmail[$email])) {
            if ($mergeExisting) {
                $existing = $existingContactsByEmail[$email];
                if (!$dryRun) {
                    mergeExistingClient($db, $clientsTable, $contactsTable, $existing, $payload);
                }
                $stats['updated_clients']++;
                $stats['updated_contacts']++;
            } else {
                $stats['skipped_existing']++;
            }
            continue;
        }

        if ($dryRun) {
            $stats['created_clients']++;
            $stats['created_contacts']++;
            continue;
        }

        $db->begin_transaction();

        $clientId = insertRow($db, $clientsTable, $payload['client']);
        $contact = $payload['contact'];
        $contact['userid'] = $clientId;

        insertRow($db, $contactsTable, $contact);
        $db->commit();

        $stats['created_clients']++;
        $stats['created_contacts']++;
    } catch (Throwable $error) {
        if ($db->errno) {
            try {
                $db->rollback();
            } catch (Throwable $rollbackError) {
                // Ignore rollback errors to preserve the original failure context.
            }
        }

        $stats['failed']++;
        $failures[] = [
            'email' => $email,
            'reason' => $error->getMessage(),
        ];
    }
}

echo json_encode([
    'mode' => $dryRun ? 'dry-run' : 'import',
    'merge_existing' => $mergeExisting,
    'tables' => [
        'clients' => $clientsTable,
        'contacts' => $contactsTable,
    ],
    'stats' => $stats,
    'sample_failures' => array_slice($failures, 0, 20),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function detectPerfexPrefix(mysqli $db): string
{
    $result = $db->query("SHOW TABLES LIKE '%clients'");
    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        $table = (string) $row[0];
        if (str_ends_with($table, 'clients')) {
            return substr($table, 0, -strlen('clients'));
        }
    }

    throw new RuntimeException('Unable to detect Perfex table prefix.');
}

function getTableColumns(mysqli $db, string $table): array
{
    $result = $db->query("SHOW COLUMNS FROM `{$table}`");
    $columns = [];

    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }

    return $columns;
}

function buildCountryMap(mysqli $db, string $countriesTable): array
{
    $map = [];

    if (!tableExists($db, $countriesTable)) {
        return $map;
    }

    $result = $db->query("SELECT country_id, iso2, short_name, long_name FROM `{$countriesTable}`");
    while ($row = $result->fetch_assoc()) {
        $countryId = (int) $row['country_id'];
        foreach ([$row['iso2'] ?? '', $row['short_name'] ?? '', $row['long_name'] ?? ''] as $value) {
            $normalized = normalizeText($value);
            if ($normalized !== '') {
                $map[$normalized] = $countryId;
            }
        }
    }

    return $map;
}

function tableExists(mysqli $db, string $table): bool
{
    $escaped = $db->real_escape_string($table);
    $result = $db->query("SHOW TABLES LIKE '{$escaped}'");
    return $result->num_rows > 0;
}

function loadSourceUsers(mysqli $db, array $userColumns): array
{
    $selectFields = array_values(array_filter([
        'id',
        'firstname',
        'lastname',
        'username',
        'email',
        'mobile',
        'dial_code',
        'country_name',
        'country_code',
        'city',
        'state',
        'zip',
        'zip_code',
        'postal_code',
        'address',
        'status',
        'created_at',
        'account_number',
        'ref_by',
        array_key_exists('role', $userColumns) ? 'role' : null,
    ], static fn ($field) => $field !== null && array_key_exists($field, $userColumns)));

    if (!in_array('email', $selectFields, true)) {
        throw new RuntimeException('Source users table does not include an email column.');
    }

    $where = ["TRIM(COALESCE(email, '')) <> ''"];
    if (array_key_exists('role', $userColumns)) {
        $where[] = "LOWER(COALESCE(role, '')) IN ('user','client')";
    }

    $sql = sprintf(
        'SELECT %s FROM `users` WHERE %s ORDER BY id ASC',
        implode(', ', array_map(static fn ($field) => "`{$field}`", $selectFields)),
        implode(' AND ', $where)
    );

    $result = $db->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function loadExistingContactsByEmail(mysqli $db, string $contactsTable): array
{
    $result = $db->query(
        "SELECT id, userid, email FROM `{$contactsTable}` WHERE TRIM(COALESCE(email, '')) <> ''"
    );

    $map = [];
    while ($row = $result->fetch_assoc()) {
        $email = normalizeEmail($row['email'] ?? '');
        if ($email === '' || isset($map[$email])) {
            continue;
        }
        $map[$email] = [
            'contact_id' => (int) $row['id'],
            'client_id' => (int) $row['userid'],
            'email' => $email,
        ];
    }

    return $map;
}

function buildPerfexPayload(array $user, array $clientColumns, array $contactColumns, array $countryMap): array
{
    $email = normalizeEmail($user['email'] ?? '');
    $firstName = trim((string) ($user['firstname'] ?? ''));
    $lastName = trim((string) ($user['lastname'] ?? ''));
    $fullName = trim($firstName . ' ' . $lastName);

    if ($fullName === '') {
        $fallback = trim((string) ($user['username'] ?? ''));
        $fullName = $fallback !== '' ? $fallback : $email;
        [$firstName, $lastName] = splitName($fullName);
    }

    if ($firstName === '') {
        [$firstName, $lastName] = splitName($fullName);
    }

    $phone = buildPhone($user);
    $address = flattenAddress($user['address'] ?? '');
    $countryId = resolveCountryId($countryMap, $user);
    $createdAt = formatTimestamp($user['created_at'] ?? null);
    $isActive = normalizeStatusFlag($user['status'] ?? null);

    $client = filterInsertableFields($clientColumns, [
        'company' => $fullName !== '' ? $fullName : $email,
        'vat' => '',
        'phonenumber' => $phone,
        'country' => $countryId,
        'city' => trim((string) ($user['city'] ?? '')),
        'zip' => firstNonEmpty($user['zip'] ?? null, $user['zip_code'] ?? null, $user['postal_code'] ?? null),
        'state' => trim((string) ($user['state'] ?? '')),
        'address' => $address,
        'website' => '',
        'datecreated' => $createdAt,
        'active' => $isActive ? 1 : 0,
        'leadid' => null,
        'billing_street' => $address,
        'billing_city' => trim((string) ($user['city'] ?? '')),
        'billing_state' => trim((string) ($user['state'] ?? '')),
        'billing_zip' => firstNonEmpty($user['zip'] ?? null, $user['zip_code'] ?? null, $user['postal_code'] ?? null),
        'billing_country' => $countryId,
        'shipping_street' => $address,
        'shipping_city' => trim((string) ($user['city'] ?? '')),
        'shipping_state' => trim((string) ($user['state'] ?? '')),
        'shipping_zip' => firstNonEmpty($user['zip'] ?? null, $user['zip_code'] ?? null, $user['postal_code'] ?? null),
        'shipping_country' => $countryId,
        'show_primary_contact' => 1,
    ]);

    $contact = filterInsertableFields($contactColumns, [
        'firstname' => $firstName !== '' ? $firstName : 'Client',
        'lastname' => $lastName,
        'email' => $email,
        'phonenumber' => $phone,
        'title' => '',
        'datecreated' => $createdAt,
        'is_primary' => 1,
        'active' => 1,
        'email_verified_at' => $createdAt,
        'password' => password_hash(bin2hex(random_bytes(12)), PASSWORD_BCRYPT),
        'invoice_emails' => 0,
        'estimate_emails' => 0,
        'credit_note_emails' => 0,
        'contract_emails' => 0,
        'task_emails' => 0,
        'project_emails' => 0,
        'ticket_emails' => 0,
    ]);

    return [
        'client' => $client,
        'contact' => $contact,
    ];
}

function filterInsertableFields(array $columns, array $values): array
{
    $output = [];

    foreach ($values as $field => $value) {
        if (!array_key_exists($field, $columns)) {
            continue;
        }

        if ($value === null) {
            if (($columns[$field]['Null'] ?? 'YES') === 'NO') {
                continue;
            }
            $output[$field] = null;
            continue;
        }

        $output[$field] = $value;
    }

    return $output;
}

function insertRow(mysqli $db, string $table, array $data): int
{
    $fields = array_keys($data);
    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $sql = sprintf(
        'INSERT INTO `%s` (%s) VALUES (%s)',
        $table,
        implode(', ', array_map(static fn ($field) => "`{$field}`", $fields)),
        $placeholders
    );

    $statement = $db->prepare($sql);
    bindStatementValues($statement, array_values($data));
    $statement->execute();

    return (int) $db->insert_id;
}

function mergeExistingClient(mysqli $db, string $clientsTable, string $contactsTable, array $existing, array $payload): void
{
    $clientFields = $payload['client'];
    unset($clientFields['datecreated']);
    updateRow($db, $clientsTable, 'userid', $existing['client_id'], $clientFields);

    $contactFields = $payload['contact'];
    unset($contactFields['datecreated'], $contactFields['password'], $contactFields['is_primary']);
    updateRow($db, $contactsTable, 'id', $existing['contact_id'], $contactFields);
}

function updateRow(mysqli $db, string $table, string $keyField, int $keyValue, array $data): void
{
    if (!$data) {
        return;
    }

    $assignments = implode(', ', array_map(static fn ($field) => "`{$field}` = ?", array_keys($data)));
    $sql = sprintf('UPDATE `%s` SET %s WHERE `%s` = ?', $table, $assignments, $keyField);

    $statement = $db->prepare($sql);
    $values = array_values($data);
    $values[] = $keyValue;
    bindStatementValues($statement, $values);
    $statement->execute();
}

function bindStatementValues(mysqli_stmt $statement, array $values): void
{
    $types = '';
    $refs = [];

    foreach ($values as $index => $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
            if ($value === null) {
                $values[$index] = null;
            } else {
                $values[$index] = (string) $value;
            }
        }
        $refs[] = &$values[$index];
    }

    $statement->bind_param($types, ...$refs);
}

function splitName(string $value): array
{
    $parts = preg_split('/\s+/', trim($value)) ?: [];
    if (!$parts) {
        return ['', ''];
    }

    $firstName = array_shift($parts);
    return [$firstName, implode(' ', $parts)];
}

function buildPhone(array $user): string
{
    $dialCode = preg_replace('/\s+/', '', (string) ($user['dial_code'] ?? ''));
    $mobile = preg_replace('/\s+/', '', (string) ($user['mobile'] ?? ''));
    return trim($dialCode . $mobile) ?: trim((string) ($user['mobile'] ?? ''));
}

function flattenAddress($address): string
{
    if (is_string($address)) {
        $trimmed = trim($address);
        if ($trimmed === '') {
            return '';
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return implode(', ', array_filter([
                $decoded['address'] ?? null,
                $decoded['street'] ?? null,
                $decoded['address_line_1'] ?? null,
                $decoded['address_line_2'] ?? null,
            ], static fn ($value) => trim((string) $value) !== ''));
        }

        return $trimmed;
    }

    if (is_array($address)) {
        return implode(', ', array_filter($address, static fn ($value) => trim((string) $value) !== ''));
    }

    return '';
}

function resolveCountryId(array $countryMap, array $user): int
{
    foreach ([$user['country_name'] ?? '', $user['country_code'] ?? ''] as $value) {
        $normalized = normalizeText($value);
        if ($normalized !== '' && isset($countryMap[$normalized])) {
            return (int) $countryMap[$normalized];
        }
    }

    return 0;
}

function formatTimestamp($value): string
{
    if (!$value) {
        return date('Y-m-d H:i:s');
    }

    $timestamp = strtotime((string) $value);
    if ($timestamp === false) {
        return date('Y-m-d H:i:s');
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function normalizeStatusFlag($value): bool
{
    if ($value === null || $value === '') {
        return true;
    }

    if (is_numeric($value)) {
        return (int) $value !== 0;
    }

    $normalized = normalizeText((string) $value);
    return !in_array($normalized, ['banned', 'disabled', 'inactive', 'suspended'], true);
}

function normalizeEmail(string $value): string
{
    return strtolower(trim($value));
}

function normalizeText(string $value): string
{
    return strtolower(trim($value));
}

function firstNonEmpty(...$values): string
{
    foreach ($values as $value) {
        $text = trim((string) $value);
        if ($text !== '') {
            return $text;
        }
    }

    return '';
}
