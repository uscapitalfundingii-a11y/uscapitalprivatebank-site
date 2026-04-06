<?php

$needle = $argv[1] ?? 'projects.uscpb.net';
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

$tables = [
    ['tbloptions', 'name', 'value'],
    ['tblmodules', 'module_name', 'active'],
    ['tblstaff', 'firstname', 'email'],
];

foreach ($tables as [$table, $col1, $col2]) {
    $columns = [];
    $res = $mysqli->query("SHOW COLUMNS FROM `$table`");
    while ($res && ($row = $res->fetch_assoc())) {
        $columns[] = $row['Field'];
    }
    foreach ($columns as $column) {
        $sql = "SELECT `{$column}` AS value FROM `{$table}` WHERE `{$column}` LIKE ?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            continue;
        }
        $like = '%' . $needle . '%';
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($result && ($row = $result->fetch_assoc())) {
            echo $table . '.' . $column . '=' . $row['value'] . PHP_EOL;
        }
        $stmt->close();
    }
}

