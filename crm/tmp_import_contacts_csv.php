<?php
// One-time importer. See D:\GithubRepos\AGENTS.md for workspace rules.
error_reporting(E_ALL);
ini_set('display_errors', '1');

$crmRoot = __DIR__;
define('BASEPATH', $crmRoot . '/application/');
define('APPPATH', $crmRoot . '/application/');

require $crmRoot . '/application/config/app-config.php';
require_once $crmRoot . '/application/third_party/phpass.php';

$csvPath = $crmRoot . '/tmp_import_contacts.csv';
if (! file_exists($csvPath)) {
    fwrite(STDERR, "Import CSV not found: {$csvPath}\n");
    exit(1);
}

$mysqli = new mysqli(
    APP_DB_HOSTNAME,
    APP_DB_USERNAME,
    APP_DB_PASSWORD,
    APP_DB_NAME
);

if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
    exit(1);
}

$mysqli->set_charset('utf8mb4');

$prefix = 'tbl';
$clientsTable = $prefix . 'clients';
$contactsTable = $prefix . 'contacts';
$permissionsTable = $prefix . 'contact_permissions';
$countriesTable = $prefix . 'countries';

$hasher = new PasswordHash(8, true);

function table_columns(mysqli $mysqli, $table)
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

function normalize_email($email)
{
    $email = strtolower(trim((string) $email));
    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    return $email;
}

function clean_text($value)
{
    return trim((string) $value);
}

function derive_name_parts(array $row)
{
    $first = clean_text($row['First Name'] ?? '');
    $last  = clean_text($row['Last Name'] ?? '');

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

    $email = normalize_email($row['Email'] ?? '');
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

    if ($first === '') {
        $first = 'Client';
    }

    if ($last === '') {
        $last = 'Contact';
    }

    return [$first, $last];
}

function build_company(array $row, $first, $last)
{
    $company = clean_text($row['Company'] ?? '');
    if ($company !== '') {
        return $company;
    }

    return trim($first . ' ' . $last);
}

function build_phone(array $row)
{
    foreach (['Phone'] as $field) {
        $value = clean_text($row[$field] ?? '');
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function fetch_country_map(mysqli $mysqli, $table)
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

function resolve_default_country_id(mysqli $mysqli, $table, array $countryMap)
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

function statement_execute(mysqli_stmt $stmt, array $params, $types)
{
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    if (! $stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }
}

$clientCols = table_columns($mysqli, $clientsTable);
$contactCols = table_columns($mysqli, $contactsTable);
$countryMap = fetch_country_map($mysqli, $countriesTable);
$defaultCountryId = resolve_default_country_id($mysqli, $countriesTable, $countryMap);

$permissionIds = [1, 2, 3, 4, 5, 6];

$existingEmails = [];
$result = $mysqli->query("SELECT id, userid, email FROM `{$contactsTable}` WHERE email IS NOT NULL AND email != ''");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $email = normalize_email($row['email']);
        if ($email) {
            $existingEmails[$email] = [
                'contact_id' => (int) $row['id'],
                'customer_id' => (int) $row['userid'],
            ];
        }
    }
    $result->free();
}

$handle = fopen($csvPath, 'rb');
if (! $handle) {
    fwrite(STDERR, "Failed to open CSV.\n");
    exit(1);
}

$headers = fgetcsv($handle);
if (! $headers) {
    fwrite(STDERR, "CSV is empty.\n");
    exit(1);
}

$stats = [
    'rows_scanned' => 0,
    'created_customers' => 0,
    'created_contacts' => 0,
    'skipped_existing_email' => 0,
    'skipped_invalid_email' => 0,
    'errors' => 0,
];

$errors = [];

while (($data = fgetcsv($handle)) !== false) {
    $stats['rows_scanned']++;
    $row = [];
    foreach ($headers as $index => $header) {
        $row[$header] = $data[$index] ?? '';
    }

    $email = normalize_email($row['Email'] ?? '');
    if (! $email) {
        $stats['skipped_invalid_email']++;
        continue;
    }

    if (isset($existingEmails[$email])) {
        $stats['skipped_existing_email']++;
        continue;
    }

    [$first, $last] = derive_name_parts($row);
    $company = build_company($row, $first, $last);
    $phone = build_phone($row);
    $countryValue = strtolower(clean_text($row['Country'] ?? ''));
    $countryId = $countryValue !== '' && isset($countryMap[$countryValue]) ? $countryMap[$countryValue] : $defaultCountryId;
    $passwordPlain = bin2hex(random_bytes(8)) . 'Aa1!';
    $passwordHash = $hasher->HashPassword($passwordPlain);
    $now = date('Y-m-d H:i:s');

    $clientData = [];
    foreach ([
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
    ] as $column => $value) {
        if (isset($clientCols[$column])) {
            $clientData[$column] = $value;
        }
    }

    $contactData = [];
    foreach ([
        'userid' => null,
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
    ] as $column => $value) {
        if (isset($contactCols[$column])) {
            $contactData[$column] = $value;
        }
    }

    try {
        $mysqli->begin_transaction();

        $clientColumns = array_keys($clientData);
        $clientValues = array_values($clientData);
        $clientTypes = str_repeat('s', count($clientValues));
        $clientSql = "INSERT INTO `{$clientsTable}` (`" . implode('`,`', $clientColumns) . "`) VALUES (" . implode(',', array_fill(0, count($clientColumns), '?')) . ")";
        $clientStmt = $mysqli->prepare($clientSql);
        if (! $clientStmt) {
            throw new RuntimeException($mysqli->error);
        }
        statement_execute($clientStmt, $clientValues, $clientTypes);
        $clientId = (int) $mysqli->insert_id;
        $clientStmt->close();

        $contactData['userid'] = $clientId;
        $contactColumns = array_keys($contactData);
        $contactValues = array_values($contactData);
        $contactTypes = str_repeat('s', count($contactValues));
        $contactSql = "INSERT INTO `{$contactsTable}` (`" . implode('`,`', $contactColumns) . "`) VALUES (" . implode(',', array_fill(0, count($contactColumns), '?')) . ")";
        $contactStmt = $mysqli->prepare($contactSql);
        if (! $contactStmt) {
            throw new RuntimeException($mysqli->error);
        }
        statement_execute($contactStmt, $contactValues, $contactTypes);
        $contactId = (int) $mysqli->insert_id;
        $contactStmt->close();

        foreach ($permissionIds as $permissionId) {
            $permStmt = $mysqli->prepare("INSERT INTO `{$permissionsTable}` (`userid`, `permission_id`) VALUES (?, ?)");
            if (! $permStmt) {
                throw new RuntimeException($mysqli->error);
            }
            $permStmt->bind_param('ii', $contactId, $permissionId);
            if (! $permStmt->execute()) {
                $permStmt->close();
                throw new RuntimeException($permStmt->error);
            }
            $permStmt->close();
        }

        $mysqli->commit();

        $existingEmails[$email] = [
            'contact_id' => $contactId,
            'customer_id' => $clientId,
        ];
        $stats['created_customers']++;
        $stats['created_contacts']++;
    } catch (Throwable $e) {
        $mysqli->rollback();
        $stats['errors']++;
        $errors[] = [
            'email' => $email,
            'message' => $e->getMessage(),
        ];
    }
}

fclose($handle);

echo json_encode([
    'stats' => $stats,
    'errors' => array_slice($errors, 0, 20),
], JSON_PRETTY_PRINT), PHP_EOL;
