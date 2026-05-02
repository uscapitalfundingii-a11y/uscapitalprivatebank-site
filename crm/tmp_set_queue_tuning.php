<?php

defined('BASEPATH') or define('BASEPATH', __DIR__ . DIRECTORY_SEPARATOR);
defined('APPPATH') or define('APPPATH', __DIR__ . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require_once __DIR__ . '/application/config/app-config.php';

if (! function_exists('db_prefix')) {
    function db_prefix()
    {
        return defined('APP_DB_PREFIX') ? APP_DB_PREFIX : 'tbl';
    }
}

$batchSize    = 2;
$interval     = 60;
$cycleMinutes = 120;
$pauseMinutes = 30;

$mysqli = @new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);

if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connection failed: {$mysqli->connect_error}\n");
    exit(1);
}

$mysqli->set_charset(defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8mb4');

$prefix  = db_prefix();
$table   = $prefix . 'options';
$updates = [
    'email_queue_enabled'              => '1',
    'email_queue_batch_size'           => (string) $batchSize,
    'email_queue_interval_seconds'     => (string) $interval,
    'email_queue_active_cycle_minutes' => (string) $cycleMinutes,
    'email_queue_pause_minutes'        => (string) $pauseMinutes,
];

$stmt = $mysqli->prepare("INSERT INTO `{$table}` (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

if (! $stmt) {
    fwrite(STDERR, "Prepare failed: {$mysqli->error}\n");
    exit(1);
}

foreach ($updates as $name => $value) {
    $stmt->bind_param('ss', $name, $value);
    if (! $stmt->execute()) {
        fwrite(STDERR, "Failed updating {$name}: {$stmt->error}\n");
        exit(1);
    }
}

$stmt->close();
$mysqli->close();

echo json_encode([
    'status'                       => 'ok',
    'email_queue_batch_size'       => $batchSize,
    'email_queue_interval_seconds' => $interval,
    'email_queue_active_cycle'     => $cycleMinutes,
    'email_queue_pause_minutes'    => $pauseMinutes,
], JSON_PRETTY_PRINT) . PHP_EOL;
