<?php

$configPath = '/home/dh_9a4ezr/uscapitalprivatebank.com/crm/application/config/app-config.php';
$config = @file_get_contents($configPath);

if ($config === false) {
    fwrite(STDERR, "config-read-failed\n");
    exit(1);
}

$patterns = [
    'host' => "/\\\$config\\['db_hostname'\\]\\s*=\\s*'([^']+)'\\s*;/",
    'user' => "/\\\$config\\['db_username'\\]\\s*=\\s*'([^']+)'\\s*;/",
    'pass' => "/\\\$config\\['db_password'\\]\\s*=\\s*'([^']*)'\\s*;/",
    'name' => "/\\\$config\\['db_name'\\]\\s*=\\s*'([^']+)'\\s*;/",
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
    'tblmodules',
    'tbloptions',
    'tblwhatsapp_interactions',
    'tblwhatsapp_interaction_messages',
    'tblwhatsapp_interaction_templates',
    'tblwhatsapp_bot',
    'tblwhatsapp_campaigns',
    'tblwhatsapp_campaign_data',
    'tblwhatsapp_activity_log',
    'tblwhatsapp_numbers',
    'tblquick_replies',
    'tblinteraction_menu_state',
    'tblwhatsapp_contacts',
    'tblwhatsapp_groups',
    'tblwhatsapp_contact_group',
];

foreach ($tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '{$table}'");
    echo $table . ':' . (($result && $result->num_rows) ? 'yes' : 'no') . PHP_EOL;
}

$result = $mysqli->query("SELECT module_name, active FROM tblmodules ORDER BY module_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo 'module=' . $row['module_name'] . ' active=' . $row['active'] . PHP_EOL;
    }
} else {
    echo 'tblmodules-query-failed:' . $mysqli->error . PHP_EOL;
}

$result = $mysqli->query("SELECT option_name, LENGTH(option_value) AS len FROM tbloptions WHERE option_name LIKE 'whatsapp_%' ORDER BY option_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo 'option=' . $row['option_name'] . ' len=' . $row['len'] . PHP_EOL;
    }
}

