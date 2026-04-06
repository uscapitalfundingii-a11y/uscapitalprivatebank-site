<?php

declare(strict_types=1);

$crmRoot = '/home/dh_9a4ezr/uscapitalprivatebank.com/crm';
$configFile = $crmRoot . '/application/config/app-config.php';

if (!file_exists($configFile)) {
    fwrite(STDERR, "missing_config\n");
    exit(1);
}

$config = file_get_contents($configFile);
if ($config === false) {
    fwrite(STDERR, "read_failed\n");
    exit(1);
}

$readDefine = static function (string $constant) use ($config): string {
    $pattern = "/define\\('" . preg_quote($constant, '/') . "',\\s*'([^']*)'\\);/";
    if (!preg_match($pattern, $config, $matches)) {
        fwrite(STDERR, "missing_define:$constant\n");
        exit(1);
    }

    return $matches[1];
};

$dbHost = $readDefine('APP_DB_HOSTNAME');
$dbUser = $readDefine('APP_DB_USERNAME');
$dbPass = $readDefine('APP_DB_PASSWORD');
$dbName = $readDefine('APP_DB_NAME');

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
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
