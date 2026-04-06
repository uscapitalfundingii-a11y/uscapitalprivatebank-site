<?php

if ($argc < 2) {
    fwrite(STDERR, "usage: php activate_perfex_module.php <module_name>\n");
    exit(1);
}

$moduleName = $argv[1];
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

$safeModule = $mysqli->real_escape_string($moduleName);
$result = $mysqli->query("UPDATE tblmodules SET active = 1 WHERE module_name = '{$safeModule}'");

if (!$result) {
    fwrite(STDERR, "update-failed: {$mysqli->error}\n");
    exit(1);
}

$verify = $mysqli->query("SELECT module_name, active FROM tblmodules WHERE module_name = '{$safeModule}'");
if ($verify && ($row = $verify->fetch_assoc())) {
    echo 'module=' . $row['module_name'] . ' active=' . $row['active'] . PHP_EOL;
    exit(0);
}

fwrite(STDERR, "verify-failed\n");
exit(1);

