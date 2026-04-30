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

$options = getopt('', ['dry-run', 'merge', 'csv:', 'csv-only']);
$dryRun = array_key_exists('dry-run', $options);
$mergeExisting = array_key_exists('merge', $options) || !array_key_exists('dry-run', $options);
$csvPaths = normalizeCsvPaths($options['csv'] ?? null);
$csvOnly = array_key_exists('csv-only', $options);

$perfexDb = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
$perfexDb->set_charset(APP_DB_CHARSET);

$sourceUsers = [];
$sourceReport = [
    'database' => [
        'enabled' => !$csvOnly,
        'label' => '',
        'rows_loaded' => 0,
    ],
    'csv_files' => [],
];

if (!$csvOnly) {
    $sourceConfig = resolveSourceDatabaseConfig();
    $sourceDb = new mysqli(
        $sourceConfig['host'],
        $sourceConfig['username'],
        $sourceConfig['password'],
        $sourceConfig['database'],
        (int) $sourceConfig['port']
    );
    $sourceDb->set_charset(APP_DB_CHARSET);
    $userColumns = getTableColumns($sourceDb, 'users');
    $dbUsers = loadSourceUsers($sourceDb, $userColumns);
    foreach ($dbUsers as &$user) {
        $user['_source'] = 'database:' . $sourceConfig['database'];
    }
    unset($user);

    $sourceUsers = array_merge($sourceUsers, $dbUsers);
    $sourceReport['database']['label'] = $sourceConfig['database'];
    $sourceReport['database']['rows_loaded'] = count($dbUsers);
}

foreach ($csvPaths as $csvPath) {
    $csvUsers = loadSourceUsersFromCsv($csvPath);
    foreach ($csvUsers as &$user) {
        $user['_source'] = 'csv:' . $csvPath;
    }
    unset($user);

    $sourceUsers = array_merge($sourceUsers, $csvUsers);
    $sourceReport['csv_files'][] = [
        'path' => $csvPath,
        'rows_loaded' => count($csvUsers),
    ];
}

$sourceUsers = dedupeSourceUsers($sourceUsers);

$prefix = detectPerfexPrefix($perfexDb);
$clientsTable = $prefix . 'clients';
$contactsTable = $prefix . 'contacts';
$countriesTable = $prefix . 'countries';

$clientColumns = getTableColumns($perfexDb, $clientsTable);
$contactColumns = getTableColumns($perfexDb, $contactsTable);
$countryMap = buildCountryMap($perfexDb, $countriesTable);

$existingContactsByEmail = loadExistingContactsByEmail($perfexDb, $contactsTable);

$stats = [
    'source_rows_loaded' => calculateSourceRowCount($sourceReport),
    'eligible_users' => count($sourceUsers),
    'created_clients' => 0,
    'created_contacts' => 0,
    'updated_clients' => 0,
    'updated_contacts' => 0,
    'skipped_existing' => 0,
    'failed' => 0,
    'source_duplicates_collapsed' => calculateSourceRowCount($sourceReport) - count($sourceUsers),
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
                    mergeExistingClient($perfexDb, $clientsTable, $contactsTable, $existing, $payload);
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
            $existingContactsByEmail[$email] = [
                'contact_id' => 0,
                'client_id' => 0,
                'email' => $email,
            ];
            continue;
        }

        $perfexDb->begin_transaction();

        $clientId = insertRow($perfexDb, $clientsTable, $payload['client']);
        $contact = $payload['contact'];
        $contact['userid'] = $clientId;

        insertRow($perfexDb, $contactsTable, $contact);
        $perfexDb->commit();

        $stats['created_clients']++;
        $stats['created_contacts']++;
        $existingContactsByEmail[$email] = [
            'contact_id' => (int) $perfexDb->insert_id,
            'client_id' => $clientId,
            'email' => $email,
        ];
    } catch (Throwable $error) {
        if ($perfexDb->errno) {
            try {
                $perfexDb->rollback();
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
    'source' => $sourceReport,
    'stats' => $stats,
    'sample_failures' => array_slice($failures, 0, 20),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function normalizeCsvPaths($value): array
{
    if ($value === null || $value === '') {
        return [];
    }

    $rawValues = is_array($value) ? $value : [$value];
    $paths = [];

    foreach ($rawValues as $rawValue) {
        foreach (explode(',', (string) $rawValue) as $candidate) {
            $path = trim($candidate);
            if ($path !== '') {
                $paths[] = $path;
            }
        }
    }

    return array_values(array_unique($paths));
}

function calculateSourceRowCount(array $sourceReport): int
{
    $count = (int) ($sourceReport['database']['rows_loaded'] ?? 0);
    foreach ($sourceReport['csv_files'] ?? [] as $csvFile) {
        $count += (int) ($csvFile['rows_loaded'] ?? 0);
    }

    return $count;
}

function resolveSourceDatabaseConfig(): array
{
    $defaults = [
        'host' => APP_DB_HOSTNAME,
        'port' => 3306,
        'database' => APP_DB_NAME,
        'username' => APP_DB_USERNAME,
        'password' => APP_DB_PASSWORD,
    ];

    $envPath = dirname(__DIR__) . '/core/.env';
    if (!is_file($envPath)) {
        return $defaults;
    }

    $env = parseEnvFile($envPath);
    return [
        'host' => $env['DB_HOST'] ?? $defaults['host'],
        'port' => $env['DB_PORT'] ?? $defaults['port'],
        'database' => $env['DB_DATABASE'] ?? $defaults['database'],
        'username' => $env['DB_USERNAME'] ?? $defaults['username'],
        'password' => $env['DB_PASSWORD'] ?? $defaults['password'],
    ];
}

function parseEnvFile(string $path): array
{
    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $values;
}

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

function dedupeSourceUsers(array $users): array
{
    $deduped = [];

    foreach ($users as $user) {
        $email = normalizeEmail($user['email'] ?? '');
        if ($email === '') {
            $deduped[] = $user;
            continue;
        }

        if (!isset($deduped[$email])) {
            $deduped[$email] = $user;
            continue;
        }

        $deduped[$email] = mergeSourceUsers($deduped[$email], $user);
    }

    $ordered = [];
    foreach ($deduped as $key => $user) {
        if (is_int($key)) {
            $ordered[] = $user;
            continue;
        }

        $ordered[] = $user;
    }

    return $ordered;
}

function mergeSourceUsers(array $base, array $incoming): array
{
    foreach ($incoming as $field => $value) {
        if ($field === '_source') {
            $base['_source'] = mergeSourceLabels($base['_source'] ?? '', (string) $value);
            continue;
        }

        if (isPreferredIncomingValue($base[$field] ?? null, $value)) {
            $base[$field] = $value;
        }
    }

    return $base;
}

function mergeSourceLabels(string $base, string $incoming): string
{
    $labels = [];

    foreach ([$base, $incoming] as $value) {
        foreach (explode('|', $value) as $label) {
            $trimmed = trim($label);
            if ($trimmed !== '') {
                $labels[$trimmed] = true;
            }
        }
    }

    return implode('|', array_keys($labels));
}

function isPreferredIncomingValue($current, $incoming): bool
{
    $incomingText = trim((string) $incoming);
    if ($incomingText === '') {
        return false;
    }

    $currentText = trim((string) $current);
    if ($currentText === '') {
        return true;
    }

    return strlen($incomingText) > strlen($currentText);
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

function loadSourceUsersFromCsv(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("CSV source file not found: {$path}");
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException("Unable to open CSV source file: {$path}");
    }

    $headers = fgetcsv($handle);
    if (!is_array($headers) || !$headers) {
        fclose($handle);
        throw new RuntimeException("CSV source file has no header row: {$path}");
    }

    $normalizedHeaders = array_map(
        static fn ($header) => trim((string) $header, " \t\n\r\0\x0B\""),
        $headers
    );

    $rows = [];
    while (($values = fgetcsv($handle)) !== false) {
        if ($values === [null] || $values === false) {
            continue;
        }

        $row = [];
        foreach ($normalizedHeaders as $index => $header) {
            $row[$header] = $values[$index] ?? '';
        }

        $row['email'] = $row['email'] ?? ($row['user_email'] ?? '');
        $row['firstname'] = $row['firstname'] ?? ($row['first_name'] ?? '');
        $row['lastname'] = $row['lastname'] ?? ($row['last_name'] ?? '');
        $row['country_name'] = $row['country_name'] ?? ($row['country'] ?? '');
        $row['postal_code'] = $row['postal_code'] ?? ($row['zip_code'] ?? ($row['zip'] ?? ''));
        $row['created_at'] = $row['created_at'] ?? ($row['created_date'] ?? '');

        $rows[] = $row;
    }

    fclose($handle);
    return $rows;
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
