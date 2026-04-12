<?php
define('BASEPATH', __DIR__ . '/application/');
require __DIR__ . '/application/config/app-config.php';
$m = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($m->connect_error) { echo 'CONNECT_ERR: ' . $m->connect_error . PHP_EOL; exit(1); }
function opt($m,$name){ $n=$m->real_escape_string($name); $r=$m->query("SELECT value FROM tbloptions WHERE name='$n' LIMIT 1"); $row=$r?$r->fetch_assoc():null; return $row?$row['value']:null; }
$r=$m->query("SELECT email,mail_password FROM tblstaff WHERE staffid=1 LIMIT 1");
$staff=$r->fetch_assoc();
require __DIR__ . '/modules/mailbox/third_party/php-imap/Imap.php';
$imap = new Imap(opt($m,'mailbox_imap_server'), $staff['email'], $staff['mail_password'], opt($m,'mailbox_encryption'));
echo 'EMAIL='.$staff['email'].PHP_EOL;
echo 'SERVER='.opt($m,'mailbox_imap_server').PHP_EOL;
echo 'ENC='.opt($m,'mailbox_encryption').PHP_EOL;
echo 'CONNECTED=' . ($imap->isConnected() ? 'YES' : 'NO') . PHP_EOL;
if ($imap->isConnected()) {
  $imap->selectFolder(opt($m,'mailbox_folder_scan') ?: 'Inbox');
  $msgs = $imap->getUnreadMessages();
  echo 'UNREAD=' . count($msgs) . PHP_EOL;
} else {
  echo 'ERROR=' . $imap->getError() . PHP_EOL;
}
?>
