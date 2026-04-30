<?php
// Outlook contact import helper.
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
ini_set('memory_limit', '512M');

$options = getopt('', ['csv:', 'dry-run']);
$csvPath = trim((string) ($options['csv'] ?? ''));
$dryRun = array_key_exists('dry-run', $options);

if ($csvPath === '') {
    fwrite(STDERR, "--csv is required.\n");
    exit(1);
}

$db = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
$db->set_charset(APP_DB_CHARSET);

$prefix = detectPerfexPrefix($db);
$clientsTable = $prefix . 'clients';
$contactsTable = $prefix . 'contacts';
$countriesTable = $prefix . 'countries';

$clientColumns = getTableColumns($db, $clientsTable);
$contactColumns = getTableColumns($db, $contactsTable);
$countryMap = buildCountryMap($db, $countriesTable);
$existingByEmail = loadExistingContactsByEmail($db, $contactsTable);
$rows = loadOutlookRows($csvPath);

$stats = [
    'rows_loaded' => count($rows),
    'created_clients' => 0,
    'created_contacts' => 0,
    'updated_clients' => 0,
    'updated_contacts' => 0,
    'merged_by_email' => 0,
    'rows_without_email' => 0,
    'rows_skipped_blank' => 0,
    'failed' => 0,
];
$failures = [];

foreach ($rows as $index => $row) {
    try {
        $prepared = prepareOutlookRow($row, $countryMap, $clientColumns, $contactColumns);
        if ($prepared === null) {
            $stats['rows_skipped_blank']++;
            continue;
        }

        $email = normalizeEmail($prepared['contact']['email'] ?? '');
        if ($email === '') {
            $stats['rows_without_email']++;
        }

        if ($email !== '' && isset($existingByEmail[$email])) {
            if (!$dryRun) {
                mergeExisting($db, $clientsTable, $contactsTable, $existingByEmail[$email], $prepared);
            }
            $stats['updated_clients']++;
            $stats['updated_contacts']++;
            $stats['merged_by_email']++;
            continue;
        }

        if ($dryRun) {
            $stats['created_clients']++;
            $stats['created_contacts']++;
            continue;
        }

        $db->begin_transaction();
        $clientId = insertRow($db, $clientsTable, $prepared['client']);
        $contact = $prepared['contact'];
        $contact['userid'] = $clientId;
        $contactId = insertRow($db, $contactsTable, $contact);
        $db->commit();

        $stats['created_clients']++;
        $stats['created_contacts']++;

        if ($email !== '') {
            $existingByEmail[$email] = [
                'client_id' => $clientId,
                'contact_id' => $contactId,
            ];
        }
    } catch (Throwable $error) {
        try {
            $db->rollback();
        } catch (Throwable $ignored) {
        }

        $stats['failed']++;
        $failures[] = [
            'row' => $index + 2,
            'reason' => $error->getMessage(),
        ];
    }
}

echo json_encode([
    'mode' => $dryRun ? 'dry-run' : 'import',
    'csv' => $csvPath,
    'stats' => $stats,
    'sample_failures' => array_slice($failures, 0, 20),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function prepareOutlookRow(array $row, array $countryMap, array $clientColumns, array $contactColumns): ?array
{
    $firstName = trim((string) ($row['First Name'] ?? ''));
    $lastName = trim((string) ($row['Last Name'] ?? ''));
    $company = trim((string) ($row['Company'] ?? ''));
    $email = normalizeEmail(firstNonEmpty($row['E-mail Address'] ?? '', $row['E-mail 2 Address'] ?? '', $row['E-mail 3 Address'] ?? ''));
    $phone = firstNonEmpty($row['Business Phone'] ?? '', $row['Mobile Phone'] ?? '', $row['Company Main Phone'] ?? '', $row['Primary Phone'] ?? '', $row['Home Phone'] ?? '', $row['Other Phone'] ?? '');
    $address = firstNonEmpty($row['Business Street'] ?? '', $row['Home Street'] ?? '', $row['Other Street'] ?? '');
    $city = firstNonEmpty($row['Business City'] ?? '', $row['Home City'] ?? '', $row['Other City'] ?? '');
    $state = firstNonEmpty($row['Business State'] ?? '', $row['Home State'] ?? '', $row['Other State'] ?? '');
    $zip = firstNonEmpty($row['Business Postal Code'] ?? '', $row['Home Postal Code'] ?? '', $row['Other Postal Code'] ?? '');
    $countryId = resolveCountryId(
        $countryMap,
        firstNonEmpty(
            $row['Business Country/Region'] ?? '',
            $row['Business Country'] ?? '',
            $row['Home Country/Region'] ?? '',
            $row['Home Country'] ?? '',
            $row['Other Country/Region'] ?? '',
            $row['Other Country'] ?? ''
        )
    );
    $fullName = trim($firstName . ' ' . $lastName);

    if ($fullName === '' && $company !== '') {
        [$firstName, $lastName] = splitName($company);
        $fullName = trim($firstName . ' ' . $lastName);
    }

    if ($fullName === '' && $email !== '') {
        [$firstName, $lastName] = splitName($email);
        $fullName = trim($firstName . ' ' . $lastName);
    }

    if ($fullName === '' && $company === '' && $email === '' && $phone === '') {
        return null;
    }

    if ($firstName === '') {
        $firstName = $company !== '' ? $company : 'Imported';
    }

    $companyName = $company !== '' ? $company : ($fullName !== '' ? $fullName : $firstName);
    $now = date('Y-m-d H:i:s');

    $client = filterFields($clientColumns, [
        'company' => $companyName,
        'phonenumber' => $phone,
        'country' => $countryId,
        'city' => $city,
        'zip' => $zip,
        'state' => $state,
        'address' => $address,
        'billing_street' => $address,
        'billing_city' => $city,
        'billing_state' => $state,
        'billing_zip' => $zip,
        'billing_country' => $countryId,
        'shipping_street' => $address,
        'shipping_city' => $city,
        'shipping_state' => $state,
        'shipping_zip' => $zip,
        'shipping_country' => $countryId,
        'show_primary_contact' => 1,
        'datecreated' => $now,
        'active' => 1,
    ]);

    $contact = filterFields($contactColumns, [
        'firstname' => $firstName,
        'lastname' => $lastName,
        'email' => $email,
        'phonenumber' => $phone,
        'title' => trim((string) ($row['Job Title'] ?? '')),
        'is_primary' => 1,
        'active' => 1,
        'datecreated' => $now,
        'password' => password_hash(bin2hex(random_bytes(12)), PASSWORD_BCRYPT),
        'invoice_emails' => 0,
        'estimate_emails' => 0,
        'credit_note_emails' => 0,
        'contract_emails' => 0,
        'task_emails' => 0,
        'project_emails' => 0,
        'ticket_emails' => 0,
    ]);

    return ['client' => $client, 'contact' => $contact];
}

function mergeExisting(mysqli $db, string $clientsTable, string $contactsTable, array $existing, array $prepared): void
{
    $existingClient = getRow($db, $clientsTable, 'userid', (int) $existing['client_id']);
    $existingContact = getRow($db, $contactsTable, 'id', (int) $existing['contact_id']);

    if (!$existingClient || !$existingContact) {
        throw new RuntimeException('Existing contact matched by email could not be loaded.');
    }

    $clientUpdate = [];
    foreach ($prepared['client'] as $field => $value) {
        if ($value !== '' && trim((string) ($existingClient[$field] ?? '')) === '') {
            $clientUpdate[$field] = $value;
        }
    }

    $contactUpdate = [];
    foreach ($prepared['contact'] as $field => $value) {
        if (in_array($field, ['password', 'is_primary', 'datecreated'], true)) {
            continue;
        }
        if ($value !== '' && trim((string) ($existingContact[$field] ?? '')) === '') {
            $contactUpdate[$field] = $value;
        }
    }

    updateRow($db, $clientsTable, 'userid', (int) $existing['client_id'], $clientUpdate);
    updateRow($db, $contactsTable, 'id', (int) $existing['contact_id'], $contactUpdate);
}

function loadOutlookRows(string $csvPath): array
{
    if (!is_file($csvPath)) {
        throw new RuntimeException("CSV source file not found: {$csvPath}");
    }

    $handle = fopen($csvPath, 'rb');
    if ($handle === false) {
        throw new RuntimeException("Unable to open CSV source file: {$csvPath}");
    }

    $headers = fgetcsv($handle);
    if (!is_array($headers) || !$headers) {
        fclose($handle);
        throw new RuntimeException("CSV source file has no header row: {$csvPath}");
    }

    $headers = array_map(static fn ($header) => trim((string) $header, " \t\n\r\0\x0B\""), $headers);
    $rows = [];

    while (($values = fgetcsv($handle)) !== false) {
        if ($values === [null]) {
            continue;
        }
        $row = [];
        foreach ($headers as $index => $header) {
            $row[$header] = $values[$index] ?? '';
        }
        $rows[] = $row;
    }

    fclose($handle);
    return $rows;
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
    $result = $db->query("SELECT country_id, iso2, short_name, long_name FROM `{$countriesTable}`");
    while ($row = $result->fetch_assoc()) {
        foreach ([$row['iso2'], $row['short_name'], $row['long_name']] as $value) {
            $normalized = normalizeText((string) $value);
            if ($normalized !== '') {
                $map[$normalized] = (int) $row['country_id'];
            }
        }
    }
    return $map;
}

function loadExistingContactsByEmail(mysqli $db, string $contactsTable): array
{
    $result = $db->query("SELECT id, userid, email FROM `{$contactsTable}` WHERE TRIM(COALESCE(email, '')) <> ''");
    $map = [];
    while ($row = $result->fetch_assoc()) {
        $email = normalizeEmail((string) ($row['email'] ?? ''));
        if ($email === '' || isset($map[$email])) {
            continue;
        }
        $map[$email] = ['contact_id' => (int) $row['id'], 'client_id' => (int) $row['userid']];
    }
    return $map;
}

function filterFields(array $columns, array $values): array
{
    $output = [];
    foreach ($values as $field => $value) {
        if (array_key_exists($field, $columns)) {
            $output[$field] = $value;
        }
    }
    return $output;
}

function insertRow(mysqli $db, string $table, array $data): int
{
    $fields = array_keys($data);
    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $table, implode(', ', array_map(static fn ($field) => "`{$field}`", $fields)), $placeholders);
    $statement = $db->prepare($sql);
    bindValues($statement, array_values($data));
    $statement->execute();
    return (int) $db->insert_id;
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
    bindValues($statement, $values);
    $statement->execute();
}

function getRow(mysqli $db, string $table, string $keyField, int $id): ?array
{
    $statement = $db->prepare("SELECT * FROM `{$table}` WHERE `{$keyField}` = ? LIMIT 1");
    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result()->fetch_assoc();
    return $result ?: null;
}

function bindValues(mysqli_stmt $statement, array $values): void
{
    $types = '';
    $refs = [];
    foreach ($values as $index => $value) {
        $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        $values[$index] = is_array($value) ? json_encode($value) : (string) $value;
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
    $first = array_shift($parts);
    return [$first, implode(' ', $parts)];
}

function resolveCountryId(array $countryMap, string $value): int
{
    $normalized = normalizeText($value);
    return $normalized !== '' && isset($countryMap[$normalized]) ? (int) $countryMap[$normalized] : 0;
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

function normalizeEmail(string $value): string
{
    return strtolower(trim($value));
}

function normalizeText(string $value): string
{
    return strtolower(trim($value));
}
