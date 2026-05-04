<?php
// One-time insert-only importer. Future agents: read D:\GithubRepos\AGENTS.md before changing this workflow.
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);
ini_set('memory_limit', '512M');

$crmRoot = __DIR__;
define('BASEPATH', $crmRoot . '/application/');
define('APPPATH', $crmRoot . '/application/');

require $crmRoot . '/application/config/app-config.php';
require_once $crmRoot . '/application/third_party/phpass.php';

$csvPath = $argv[1] ?? ($crmRoot . '/tmp_import_contacts.csv');
if (! file_exists($csvPath)) {
    fwrite(STDERR, "Import CSV not found: {$csvPath}\n");
    exit(1);
}

$mysqli = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($mysqli->connect_errno) {
    $mysqli = new mysqli('127.0.0.1', APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
}
if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$prefix = defined('APP_DB_PREFIX') ? APP_DB_PREFIX : 'tbl';
$clientsTable = $prefix . 'clients';
$contactsTable = $prefix . 'contacts';
$permissionsTable = $prefix . 'contact_permissions';
$countriesTable = $prefix . 'countries';
$hasher = new PasswordHash(8, true);

function table_columns(mysqli $mysqli, string $table): array
{
    $columns = [];
    $result = $mysqli->query("SHOW COLUMNS FROM `{$table}`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = true;
        }
        $result->free();
    }
    return $columns;
}

function normalize_email($email): ?string
{
    $email = strtolower(trim((string) $email));
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
}

function clean_text($value): string
{
    return trim((string) $value);
}

function normalize_row_keys(array $row): array
{
    $aliases = [
        'first name' => 'First Name',
        'firstname' => 'First Name',
        'given name' => 'First Name',
        'last name' => 'Last Name',
        'lastname' => 'Last Name',
        'surname' => 'Last Name',
        'company' => 'Company',
        'business name' => 'Company',
        'title' => 'Position',
        'position' => 'Position',
        'job title' => 'Position',
        'email' => 'Email',
        'email address' => 'Email',
        'e-mail address' => 'Email',
        'alternate email' => 'Alternate Email',
        'alternate email address' => 'Alternate Email',
        'secondary email' => 'Alternate Email',
        'email 2' => 'Alternate Email',
        'email2' => 'Alternate Email',
        'e-mail 2 address' => 'Alternate Email',
        'phone' => 'Phone',
        'business phone' => 'Phone',
        'mobile phone' => 'Phone',
        'contact phonenumber' => 'Phone',
        'contact_phonenumber' => 'Phone',
        'phonenumber' => 'Phone',
        'address' => 'Address',
        'street' => 'Address',
        'business street' => 'Address',
        'city' => 'City',
        'state' => 'State',
        'province' => 'State',
        'zip' => 'Zip Code',
        'zip code' => 'Zip Code',
        'postal code' => 'Zip Code',
        'country' => 'Country',
        'website' => 'Website',
        'web page' => 'Website',
        'notes' => 'Notes',
        'comment' => 'Notes',
    ];

    $normalized = [];
    foreach ($row as $key => $value) {
        $lookup = strtolower(trim(str_replace('_', ' ', (string) $key)));
        $normalizedKey = $aliases[$lookup] ?? trim((string) $key);
        if (! isset($normalized[$normalizedKey]) || clean_text($normalized[$normalizedKey]) === '') {
            $normalized[$normalizedKey] = $value;
        }
    }

    return $normalized;
}

function fetch_country_map(mysqli $mysqli, string $table): array
{
    $map = [];
    $availableColumns = table_columns($mysqli, $table);
    $candidateColumns = ['country_id'];
    foreach (['short_name', 'iso2', 'nicename', 'name'] as $column) {
        if (isset($availableColumns[$column])) {
            $candidateColumns[] = $column;
        }
    }

    $result = $mysqli->query("SELECT `" . implode('`,`', $candidateColumns) . "` FROM `{$table}`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            foreach ($row as $field => $candidate) {
                if ($field === 'country_id') {
                    continue;
                }
                $candidate = strtolower(trim((string) $candidate));
                if ($candidate !== '' && ! isset($map[$candidate])) {
                    $map[$candidate] = (int) $row['country_id'];
                }
            }
        }
        $result->free();
    }

    return $map;
}

function resolve_default_country_id(mysqli $mysqli, string $table, array $countryMap): int
{
    foreach (['united states', 'united arab emirates', 'usa', 'us', 'uae'] as $candidate) {
        if (isset($countryMap[$candidate])) {
            return (int) $countryMap[$candidate];
        }
    }
    $result = $mysqli->query("SELECT `country_id` FROM `{$table}` ORDER BY `country_id` ASC LIMIT 1");
    if ($result) {
        $row = $result->fetch_assoc();
        $result->free();
        if ($row && isset($row['country_id'])) {
            return (int) $row['country_id'];
        }
    }
    return 0;
}

function derive_name_parts(array $row): array
{
    $first = clean_text($row['First Name'] ?? '');
    $last = clean_text($row['Last Name'] ?? '');
    if ($first !== '' && $last !== '') {
        return [$first, $last];
    }

    $company = clean_text($row['Company'] ?? '');
    if ($company !== '') {
        $parts = preg_split('/\s+/', $company);
        if ($first === '' && ! empty($parts[0])) {
            $first = $parts[0];
        }
        if ($last === '' && count($parts) > 1) {
            $last = implode(' ', array_slice($parts, 1));
        }
    }

    $email = normalize_email($row['Email'] ?? '') ?: normalize_email($row['Alternate Email'] ?? '');
    if ($email && ($first === '' || $last === '')) {
        $local = preg_replace('/[^a-z0-9]+/i', ' ', strstr($email, '@', true));
        $parts = preg_split('/\s+/', trim((string) $local));
        if ($first === '' && ! empty($parts[0])) {
            $first = ucfirst(strtolower($parts[0]));
        }
        if ($last === '' && count($parts) > 1) {
            $last = ucfirst(strtolower(implode(' ', array_slice($parts, 1))));
        }
    }

    return [$first !== '' ? $first : 'Client', $last !== '' ? $last : 'Contact'];
}

function filter_table_data(array $columns, array $candidate): array
{
    $data = [];
    foreach ($candidate as $column => $value) {
        if (isset($columns[$column])) {
            $data[$column] = $value;
        }
    }
    return $data;
}

function insert_row(mysqli $mysqli, string $table, array $row): int
{
    $fields = array_keys($row);
    $sql = "INSERT INTO `{$table}` (`" . implode('`,`', $fields) . "`) VALUES (" . implode(',', array_fill(0, count($fields), '?')) . ")";
    $stmt = $mysqli->prepare($sql);
    if (! $stmt) {
        throw new RuntimeException($mysqli->error);
    }
    $types = '';
    $values = [];
    foreach ($row as $value) {
        $types .= is_int($value) ? 'i' : 's';
        $values[] = $value;
    }
    $stmt->bind_param($types, ...$values);
    if (! $stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException($error);
    }
    $insertId = (int) $mysqli->insert_id;
    $stmt->close();
    return $insertId;
}

function insert_permissions(mysqli $mysqli, string $table, int $contactId): int
{
    $values = [];
    for ($permissionId = 1; $permissionId <= 6; $permissionId++) {
        $values[] = '(' . $contactId . ',' . $permissionId . ')';
    }
    $sql = "INSERT INTO `{$table}` (`userid`, `permission_id`) VALUES " . implode(',', $values);
    if (! $mysqli->query($sql)) {
        throw new RuntimeException($mysqli->error);
    }
    return $mysqli->affected_rows;
}

$clientCols = table_columns($mysqli, $clientsTable);
$contactCols = table_columns($mysqli, $contactsTable);
$countryMap = fetch_country_map($mysqli, $countriesTable);
$defaultCountryId = resolve_default_country_id($mysqli, $countriesTable, $countryMap);

$existingEmails = [];
$result = $mysqli->query("SELECT email FROM `{$contactsTable}` WHERE email IS NOT NULL AND email != ''");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $email = normalize_email($row['email']);
        if ($email) {
            $existingEmails[$email] = true;
        }
    }
    $result->free();
}

$handle = fopen($csvPath, 'rb');
if (! $handle) {
    fwrite(STDERR, "Failed to open CSV: {$csvPath}\n");
    exit(1);
}

$headers = fgetcsv($handle);
if (! $headers) {
    fwrite(STDERR, "CSV is empty: {$csvPath}\n");
    exit(1);
}

$stats = [
    'rows_scanned' => 0,
    'created_customers' => 0,
    'created_contacts' => 0,
    'matched_existing_email' => 0,
    'skipped_invalid_email' => 0,
    'enabled_permissions' => 0,
    'errors' => 0,
];
$errors = [];
$createdEmails = [];

while (($data = fgetcsv($handle)) !== false) {
    $stats['rows_scanned']++;
    $row = [];
    foreach ($headers as $index => $header) {
        $row[$header] = $data[$index] ?? '';
    }
    $row = normalize_row_keys($row);

    $email = normalize_email($row['Email'] ?? '') ?: normalize_email($row['Alternate Email'] ?? '');
    if (! $email) {
        $stats['skipped_invalid_email']++;
        continue;
    }
    if (isset($existingEmails[$email])) {
        $stats['matched_existing_email']++;
        continue;
    }

    [$first, $last] = derive_name_parts($row);
    $company = clean_text($row['Company'] ?? '') ?: trim($first . ' ' . $last);
    $phone = clean_text($row['Phone'] ?? '');
    $countryValue = strtolower(clean_text($row['Country'] ?? ''));
    $countryId = $countryValue !== '' && isset($countryMap[$countryValue]) ? $countryMap[$countryValue] : $defaultCountryId;
    $now = date('Y-m-d H:i:s');
    $passwordHash = $hasher->HashPassword(bin2hex(random_bytes(8)) . 'Aa1!');

    try {
        $mysqli->begin_transaction();

        $clientData = filter_table_data($clientCols, [
            'company' => $company,
            'phonenumber' => $phone,
            'website' => clean_text($row['Website'] ?? ''),
            'address' => clean_text($row['Address'] ?? ''),
            'city' => clean_text($row['City'] ?? ''),
            'state' => clean_text($row['State'] ?? ''),
            'zip' => clean_text($row['Zip Code'] ?? ''),
            'country' => $countryId,
            'billing_street' => clean_text($row['Address'] ?? ''),
            'billing_city' => clean_text($row['City'] ?? ''),
            'billing_state' => clean_text($row['State'] ?? ''),
            'billing_zip' => clean_text($row['Zip Code'] ?? ''),
            'billing_country' => $countryId,
            'shipping_street' => clean_text($row['Address'] ?? ''),
            'shipping_city' => clean_text($row['City'] ?? ''),
            'shipping_state' => clean_text($row['State'] ?? ''),
            'shipping_zip' => clean_text($row['Zip Code'] ?? ''),
            'shipping_country' => $countryId,
            'active' => 1,
            'datecreated' => $now,
            'addedfrom' => 0,
        ]);
        $clientId = insert_row($mysqli, $clientsTable, $clientData);

        $contactData = filter_table_data($contactCols, [
            'userid' => $clientId,
            'is_primary' => 1,
            'firstname' => $first,
            'lastname' => $last,
            'title' => clean_text($row['Position'] ?? ''),
            'email' => $email,
            'phonenumber' => $phone,
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
        ]);
        $contactId = insert_row($mysqli, $contactsTable, $contactData);
        $stats['enabled_permissions'] += insert_permissions($mysqli, $permissionsTable, $contactId);

        $mysqli->commit();
        $existingEmails[$email] = true;
        $stats['created_customers']++;
        $stats['created_contacts']++;
        $createdEmails[] = $email;
    } catch (Throwable $e) {
        $mysqli->rollback();
        $stats['errors']++;
        if (count($errors) < 20) {
            $errors[] = ['email' => $email, 'message' => $e->getMessage()];
        }
    }
}
fclose($handle);

$slug = preg_replace('/[^a-zA-Z0-9_.-]+/', '_', basename($csvPath));
$createdPath = $crmRoot . '/tmp_insert_only_created_' . $slug . '.json';
file_put_contents($createdPath, json_encode([
    'generated_at' => date('c'),
    'source_csv' => $csvPath,
    'created_count' => count($createdEmails),
    'emails' => $createdEmails,
], JSON_PRETTY_PRINT));

echo json_encode([
    'source_csv' => $csvPath,
    'stats' => $stats,
    'created_emails_file' => basename($createdPath),
    'created_email_count' => count($createdEmails),
    'errors' => $errors,
], JSON_PRETTY_PRINT), PHP_EOL;

