<?php

$newCallback = $argv[1] ?? 'https://www.uscapitalprivatebank.com/crm/admin/zoom_meetings/authorized';
$configPath = '/home/dh_9a4ezr/uscapitalprivatebank.com/crm/application/config/app-config.php';
$config = @file_get_contents($configPath);

if ($config === false) {
    fwrite(STDERR, "config-read-failed\n");
    exit(1);
}

$patterns = [
    'host' => "/define\\('APP_DB_HOSTNAME',\\s*'([^']+)'\\);/",
    'user' => "/define\\('APP_DB_USERNAME',\\s*'([^']+)'\\);/",
    'pass' => "/define\\('APP_DB_PASSWORD',\\s*'([^']*)'\\);/",
    'name' => "/define\\('APP_DB_NAME',\\s*'([^']+)'\\);/",
];

$values = [];
foreach ($patterns as $key => $pattern) {
    if (!preg_match($pattern, $config, $matches)) {
        fwrite(STDERR, "missing-$key\n");
        exit(1);
    }
    $values[$key] = $matches[1];
}

$mysqli = @new mysqli($values['host'], $values['user'], $values['pass'], $values['name']);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "connect-failed: {$mysqli->connect_error}\n");
    exit(1);
}

$safeCallback = $mysqli->real_escape_string($newCallback);
$result = $mysqli->query("UPDATE tblzoom SET api_key = '{$safeCallback}'");
if (!$result) {
    fwrite(STDERR, "update-failed: {$mysqli->error}\n");
    exit(1);
}

$verify = $mysqli->query("SELECT id, zoom_email, api_key FROM tblzoom LIMIT 1");
if ($verify && ($row = $verify->fetch_assoc())) {
    echo 'id=' . $row['id'] . PHP_EOL;
    echo 'zoom_email=' . $row['zoom_email'] . PHP_EOL;
    echo 'api_key=' . $row['api_key'] . PHP_EOL;
    exit(0);
}

fwrite(STDERR, "verify-failed\n");
exit(1);

