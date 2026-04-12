<?php
define('BASEPATH', __DIR__ . '/application/');
require __DIR__ . '/application/config/app-config.php';
$m = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
$q="SELECT COUNT(*) AS visible_inbox, SUM(CASE WHEN `read`='0' AND `trash`='0' THEN 1 ELSE 0 END) AS visible_unread, SUM(CASE WHEN `trash`='1' THEN 1 ELSE 0 END) AS trashed FROM tblmail_inbox WHERE to_staff_id=1";
$r=$m->query($q);
while($row=$r->fetch_assoc()){echo json_encode($row, JSON_UNESCAPED_SLASHES).PHP_EOL;}
?>
