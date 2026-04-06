<?php

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

$result = $mysqli->query("SHOW COLUMNS FROM tbloptions");
$columns = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}

echo 'tbloptions_columns=' . implode(',', $columns) . PHP_EOL;

$nameColumn = in_array('name', $columns, true) ? 'name' : (in_array('option_name', $columns, true) ? 'option_name' : null);
$valueColumn = in_array('value', $columns, true) ? 'value' : (in_array('option_value', $columns, true) ? 'option_value' : null);

if ($nameColumn && $valueColumn) {
    $result = $mysqli->query("SELECT `{$nameColumn}` AS option_name, LENGTH(`{$valueColumn}`) AS len FROM tbloptions WHERE `{$nameColumn}` LIKE 'whatsapp_%' ORDER BY `{$nameColumn}`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo 'option=' . $row['option_name'] . ' len=' . $row['len'] . PHP_EOL;
        }
    } else {
        echo 'whatsapp-options-query-failed:' . $mysqli->error . PHP_EOL;
    }
} else {
    echo 'tbloptions-name-value-columns-not-found' . PHP_EOL;
}
