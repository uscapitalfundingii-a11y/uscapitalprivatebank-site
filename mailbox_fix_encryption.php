<?php
define('BASEPATH', __DIR__ . '/application/');
require __DIR__ . '/application/config/app-config.php';
$m = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($m->connect_error) { echo 'CONNECT_ERR: ' . $m->connect_error . PHP_EOL; exit(1); }
$m->query("UPDATE tbloptions SET value='ssl' WHERE name='mailbox_encryption'");
$r = $m->query("SELECT name,value FROM tbloptions WHERE name IN ('mailbox_imap_server','mailbox_encryption') ORDER BY name");
while($row=$r->fetch_assoc()){ echo json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL; }
?>
