<?php

declare(strict_types=1);

$crmRoot = '/home/dh_9a4ezr/uscapitalprivatebank.com/crm';
$configFile = $crmRoot . '/application/config/app-config.php';

if (!file_exists($configFile)) {
    fwrite(STDERR, "missing_config\n");
    exit(1);
}

require $configFile;

$mysqli = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($mysqli->connect_errno) {
    fwrite(STDERR, 'connect_failed:' . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

$sql = "SELECT name, value FROM tbloptions WHERE name LIKE 'whatsapp\\_%' ESCAPE '\\\\' ORDER BY name";
$result = $mysqli->query($sql);
if (!$result) {
    fwrite(STDERR, 'query_failed:' . $mysqli->error . PHP_EOL);
    exit(1);
}

while ($row = $result->fetch_assoc()) {
    $preview = mb_substr((string) $row['value'], 0, 120);
    $length = strlen((string) $row['value']);
    echo $row['name'] . '|' . $length . '|' . str_replace(["\r", "\n"], ['\\r', '\\n'], $preview) . PHP_EOL;
}

$result->free();
$mysqli->close();
