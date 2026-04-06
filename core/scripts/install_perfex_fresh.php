<?php

require __DIR__ . '/../vendor/autoload.php';

$crmRoot = '/home/dh_9a4ezr/uscapitalprivatebank.com/crm';
$configSamplePath = $crmRoot . '/application/config/app-config-sample.php';
$configPath = $crmRoot . '/application/config/app-config.php';
$sqlPath = $crmRoot . '/install/database.sql';

$dbHost = 'crm.uscapitalprivatebank.com';
$dbName = 'crmuscpb';
$dbUser = 'uscpbcrm';
$dbPass = '1995?+DM=blessing#$';
$baseUrl = 'https://www.uscapitalprivatebank.com/crm/';
$timezone = 'Asia/Dubai';

$adminFirstName = 'US Capital';
$adminLastName = 'Admin';
$adminEmail = 'admin@crm.uscapitalprivatebank.com';
$adminPassword = 'WelcomeCRM2026!';

if (!file_exists($configSamplePath)) {
    fwrite(STDERR, "Config sample not found at {$configSamplePath}\n");
    exit(1);
}

if (!file_exists($sqlPath)) {
    fwrite(STDERR, "Install SQL not found at {$sqlPath}\n");
    exit(1);
}

$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$existingTables = $mysqli->query('SHOW TABLES');
if ($existingTables && $existingTables->num_rows > 0) {
    echo "Database already has tables; skipping fresh install.\n";
    exit(0);
}

$sample = file_get_contents($configSamplePath);
$config = str_replace(
    ['[db_hostname]', '[db_username]', '[db_password]', '[db_name]', '[encryption_key]', '[base_url]'],
    [$dbHost, $dbUser, addslashes($dbPass), $dbName, bin2hex(random_bytes(16)), rtrim($baseUrl, '/') . '/'],
    trim($sample)
);

if (file_put_contents($configPath, $config) === false) {
    fwrite(STDERR, "Failed to write {$configPath}\n");
    exit(1);
}

require_once $crmRoot . '/install/sqlparser.php';
require_once $crmRoot . '/install/phpass.php';

$parser = new SqlScriptParser();
$statements = $parser->parse($sqlPath);

foreach ($statements as $statement) {
    $distilled = $parser->removeComments($statement);
    if (trim($distilled) === '') {
        continue;
    }

    if (!$mysqli->query($distilled)) {
        fwrite(STDERR, "SQL failed: {$mysqli->error}\n");
        exit(1);
    }
}

$timezoneEscaped = $mysqli->real_escape_string($timezone);
$installMsg = '<div class="col-md-12"><div class="alert alert-success"><h4 class="bold">Congratulation on your installation!</h4><p>Now, you can activate modules that comes with the installation in <b>Setup->Modules<b>.</p></div></div>';
$installMsgEscaped = $mysqli->real_escape_string($installMsg);
$di = time();

$mysqli->query("UPDATE tbloptions SET value='{$timezoneEscaped}' WHERE name='default_timezone'");
$mysqli->query("UPDATE tbloptions SET value='{$di}' WHERE name='di'");
$mysqli->query("UPDATE tbloptions SET value='{$installMsgEscaped}' WHERE name='update_info_message'");

$hasher = new PasswordHash(8, false);
$hashedPassword = $mysqli->real_escape_string($hasher->HashPassword($adminPassword));
$firstName = $mysqli->real_escape_string($adminFirstName);
$lastName = $mysqli->real_escape_string($adminLastName);
$email = $mysqli->real_escape_string($adminEmail);
$dateCreated = date('Y-m-d H:i:s');

$insertAdmin = "INSERT INTO tblstaff (`firstname`, `lastname`, `password`, `email`, `datecreated`, `admin`, `active`) VALUES ('{$firstName}', '{$lastName}', '{$hashedPassword}', '{$email}', '{$dateCreated}', 1, 1)";
if (!$mysqli->query($insertAdmin)) {
    fwrite(STDERR, "Admin insert failed: {$mysqli->error}\n");
    exit(1);
}

echo "Perfex fresh install completed.\n";
echo "Admin email: {$adminEmail}\n";
echo "Admin password: {$adminPassword}\n";
