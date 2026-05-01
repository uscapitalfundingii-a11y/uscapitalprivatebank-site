<?php
// One-time importer/updater. See D:\GithubRepos\AGENTS.md for workspace rules.
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

function normalize_row_keys(array $row)
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

function is_blankish($value)
{
    return trim((string) $value) === '';
}

function choose_update_value($current, $incoming)
{
    $incoming = clean_text($incoming);
    if ($incoming === '') {
        return null;
    }

    if (is_blankish($current)) {
        return $incoming;
    }

    return null;
}

function choose_update_int($current, $incoming)
{
    if ((int) $incoming <= 0) {
        return null;
    }

    if ((int) $current <= 0) {
        return (int) $incoming;
    }

    return null;
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
    if (! $email) {
        $email = normalize_email($row['Alternate Email'] ?? '');
    }
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

function fetch_row_by_key(mysqli $mysqli, $table, $keyColumn, $id)
{
    $stmt = $mysqli->prepare("SELECT * FROM `{$table}` WHERE `{$keyColumn}` = ? LIMIT 1");
    if (! $stmt) {
        throw new RuntimeException($mysqli->error);
    }
    $stmt->bind_param('i', $id);
    if (! $stmt->execute()) {
        $stmt->close();
        throw new RuntimeException($stmt->error);
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    if ($result) {
        $result->free();
    }
    $stmt->close();

    return $row;
}

function update_table_by_key(mysqli $mysqli, $table, $keyColumn, $id, array $data)
{
    if (empty($data)) {
        return false;
    }

    $assignments = [];
    $params = [];
    $types = '';
    foreach ($data as $column => $value) {
        $assignments[] = "`{$column}` = ?";
        $params[] = $value;
        $types .= is_int($value) ? 'i' : 's';
    }
    $params[] = (int) $id;
    $types .= 'i';

    $sql = "UPDATE `{$table}` SET " . implode(', ', $assignments) . " WHERE `{$keyColumn}` = ?";
    $stmt = $mysqli->prepare($sql);
    if (! $stmt) {
        throw new RuntimeException($mysqli->error);
    }
    statement_execute($stmt, $params, $types);
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    return $changed;
}

function ensure_permissions(mysqli $mysqli, $permissionsTable, $contactId, array $permissionIds)
{
    $existing = [];
    $inserted = 0;
    $stmt = $mysqli->prepare("SELECT permission_id FROM `{$permissionsTable}` WHERE userid = ?");
    if (! $stmt) {
        throw new RuntimeException($mysqli->error);
    }
    $stmt->bind_param('i', $contactId);
    if (! $stmt->execute()) {
        $stmt->close();
        throw new RuntimeException($stmt->error);
    }
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing[(int) $row['permission_id']] = true;
        }
        $result->free();
    }
    $stmt->close();

    foreach ($permissionIds as $permissionId) {
        if (isset($existing[$permissionId])) {
            continue;
        }
        $insert = $mysqli->prepare("INSERT INTO `{$permissionsTable}` (`userid`, `permission_id`) VALUES (?, ?)");
        if (! $insert) {
            throw new RuntimeException($mysqli->error);
        }
        $insert->bind_param('ii', $contactId, $permissionId);
        if (! $insert->execute()) {
            $insert->close();
            throw new RuntimeException($insert->error);
        }
        $insert->close();
        $inserted++;
    }

    return $inserted;
}

$clientCols = table_columns($mysqli, $clientsTable);
$contactCols = table_columns($mysqli, $contactsTable);
$countryMap = fetch_country_map($mysqli, $countriesTable);
$defaultCountryId = resolve_default_country_id($mysqli, $countriesTable, $countryMap);

$permissionIds = [1, 2, 3, 4, 5, 6];
$createdEmailsPath = $crmRoot . '/tmp_import_created_emails.json';
$createdEmailsAllPath = $crmRoot . '/tmp_import_created_emails_all.json';

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
    'matched_existing_email' => 0,
    'updated_customers' => 0,
    'updated_contacts' => 0,
    'enabled_permissions' => 0,
    'enabled_notifications' => 0,
    'unchanged_existing' => 0,
    'skipped_invalid_email' => 0,
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

    $email = normalize_email($row['Email'] ?? '');
    if (! $email) {
        $email = normalize_email($row['Alternate Email'] ?? '');
    }
    if (! $email) {
        $stats['skipped_invalid_email']++;
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

    try {
        $mysqli->begin_transaction();

        if (! isset($existingEmails[$email])) {
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

            $stats['enabled_permissions'] += ensure_permissions($mysqli, $permissionsTable, $contactId, $permissionIds);

            $existingEmails[$email] = [
                'contact_id' => $contactId,
                'customer_id' => $clientId,
            ];
            $stats['created_customers']++;
            $stats['created_contacts']++;
            $createdEmails[] = $email;
        } else {
            $stats['matched_existing_email']++;
            $contactId = (int) $existingEmails[$email]['contact_id'];
            $clientId = (int) $existingEmails[$email]['customer_id'];

            $contactRow = fetch_row_by_key($mysqli, $contactsTable, 'id', $contactId);
            $clientRow = fetch_row_by_key($mysqli, $clientsTable, 'userid', $clientId);
            if (! $contactRow || ! $clientRow) {
                throw new RuntimeException('Existing contact or customer record could not be loaded.');
            }

            $contactUpdates = [];
            foreach ([
                'firstname' => choose_update_value($contactRow['firstname'] ?? '', $first),
                'lastname' => choose_update_value($contactRow['lastname'] ?? '', $last),
                'title' => choose_update_value($contactRow['title'] ?? '', clean_text($row['Position'] ?? '')),
                'phonenumber' => choose_update_value($contactRow['phonenumber'] ?? '', $phone),
                'password' => is_blankish($contactRow['password'] ?? '') ? $passwordHash : null,
                'active' => isset($contactCols['active']) && (int) ($contactRow['active'] ?? 0) !== 1 ? 1 : null,
                'email_verified_at' => isset($contactCols['email_verified_at']) && is_blankish($contactRow['email_verified_at'] ?? '') ? $now : null,
                'invoice_emails' => isset($contactCols['invoice_emails']) && (int) ($contactRow['invoice_emails'] ?? 0) !== 1 ? 1 : null,
                'estimate_emails' => isset($contactCols['estimate_emails']) && (int) ($contactRow['estimate_emails'] ?? 0) !== 1 ? 1 : null,
                'credit_note_emails' => isset($contactCols['credit_note_emails']) && (int) ($contactRow['credit_note_emails'] ?? 0) !== 1 ? 1 : null,
                'contract_emails' => isset($contactCols['contract_emails']) && (int) ($contactRow['contract_emails'] ?? 0) !== 1 ? 1 : null,
                'task_emails' => isset($contactCols['task_emails']) && (int) ($contactRow['task_emails'] ?? 0) !== 1 ? 1 : null,
                'project_emails' => isset($contactCols['project_emails']) && (int) ($contactRow['project_emails'] ?? 0) !== 1 ? 1 : null,
                'ticket_emails' => isset($contactCols['ticket_emails']) && (int) ($contactRow['ticket_emails'] ?? 0) !== 1 ? 1 : null,
                'direction' => isset($contactCols['direction']) && is_blankish($contactRow['direction'] ?? '') ? 'ltr' : null,
                'is_primary' => isset($contactCols['is_primary']) && (int) ($contactRow['is_primary'] ?? 0) !== 1 ? 1 : null,
            ] as $column => $value) {
                if (! array_key_exists($column, $contactCols)) {
                    continue;
                }
                if ($value !== null) {
                    $contactUpdates[$column] = $value;
                }
            }

            $clientUpdates = [];
            foreach ([
                'company' => choose_update_value($clientRow['company'] ?? '', $company),
                'phonenumber' => choose_update_value($clientRow['phonenumber'] ?? '', $phone),
                'website' => choose_update_value($clientRow['website'] ?? '', clean_text($row['Website'] ?? '')),
                'address' => choose_update_value($clientRow['address'] ?? '', clean_text($row['Address'] ?? '')),
                'city' => choose_update_value($clientRow['city'] ?? '', clean_text($row['City'] ?? '')),
                'state' => choose_update_value($clientRow['state'] ?? '', clean_text($row['State'] ?? '')),
                'zip' => choose_update_value($clientRow['zip'] ?? '', clean_text($row['Zip Code'] ?? '')),
                'billing_street' => choose_update_value($clientRow['billing_street'] ?? '', clean_text($row['Address'] ?? '')),
                'billing_city' => choose_update_value($clientRow['billing_city'] ?? '', clean_text($row['City'] ?? '')),
                'billing_state' => choose_update_value($clientRow['billing_state'] ?? '', clean_text($row['State'] ?? '')),
                'billing_zip' => choose_update_value($clientRow['billing_zip'] ?? '', clean_text($row['Zip Code'] ?? '')),
                'shipping_street' => choose_update_value($clientRow['shipping_street'] ?? '', clean_text($row['Address'] ?? '')),
                'shipping_city' => choose_update_value($clientRow['shipping_city'] ?? '', clean_text($row['City'] ?? '')),
                'shipping_state' => choose_update_value($clientRow['shipping_state'] ?? '', clean_text($row['State'] ?? '')),
                'shipping_zip' => choose_update_value($clientRow['shipping_zip'] ?? '', clean_text($row['Zip Code'] ?? '')),
                'country' => choose_update_int($clientRow['country'] ?? 0, $countryId),
                'billing_country' => choose_update_int($clientRow['billing_country'] ?? 0, $countryId),
                'shipping_country' => choose_update_int($clientRow['shipping_country'] ?? 0, $countryId),
                'active' => isset($clientCols['active']) && (int) ($clientRow['active'] ?? 0) !== 1 ? 1 : null,
            ] as $column => $value) {
                if (! array_key_exists($column, $clientCols)) {
                    continue;
                }
                if ($value !== null) {
                    $clientUpdates[$column] = $value;
                }
            }

            $contactChanged = update_table_by_key($mysqli, $contactsTable, 'id', $contactId, $contactUpdates);
            $clientChanged = update_table_by_key($mysqli, $clientsTable, 'userid', $clientId, $clientUpdates);
            $stats['enabled_permissions'] += ensure_permissions($mysqli, $permissionsTable, $contactId, $permissionIds);

            if ($contactChanged) {
                $stats['updated_contacts']++;
            }
            if ($clientChanged) {
                $stats['updated_customers']++;
            }
            if (! $contactChanged && ! $clientChanged) {
                $stats['unchanged_existing']++;
            }

            foreach (['invoice_emails', 'estimate_emails', 'credit_note_emails', 'contract_emails', 'task_emails', 'project_emails', 'ticket_emails'] as $field) {
                if (isset($contactUpdates[$field]) && (int) $contactUpdates[$field] === 1) {
                    $stats['enabled_notifications']++;
                }
            }
        }

        $mysqli->commit();
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

file_put_contents($createdEmailsPath, json_encode([
    'generated_at' => date('c'),
    'created_count' => count($createdEmails),
    'emails' => $createdEmails,
], JSON_PRETTY_PRINT));

$existingAllEmails = [];
if (file_exists($createdEmailsAllPath)) {
    $existingAllJson = json_decode((string) file_get_contents($createdEmailsAllPath), true);
    $existingAllList = $existingAllJson['emails'] ?? $existingAllJson;
    if (is_array($existingAllList)) {
        foreach ($existingAllList as $existingEmail) {
            $normalizedExisting = normalize_email($existingEmail);
            if ($normalizedExisting) {
                $existingAllEmails[$normalizedExisting] = true;
            }
        }
    }
}

foreach ($createdEmails as $createdEmail) {
    $normalizedCreated = normalize_email($createdEmail);
    if ($normalizedCreated) {
        $existingAllEmails[$normalizedCreated] = true;
    }
}

$allCreatedEmails = array_keys($existingAllEmails);
sort($allCreatedEmails);

file_put_contents($createdEmailsAllPath, json_encode([
    'generated_at' => date('c'),
    'created_count' => count($allCreatedEmails),
    'emails' => $allCreatedEmails,
], JSON_PRETTY_PRINT));

echo json_encode([
    'stats' => $stats,
    'created_emails_file' => basename($createdEmailsPath),
    'created_email_count' => count($createdEmails),
    'created_emails_all_file' => basename($createdEmailsAllPath),
    'created_emails_all_count' => count($allCreatedEmails),
    'errors' => array_slice($errors, 0, 20),
], JSON_PRETTY_PRINT), PHP_EOL;
