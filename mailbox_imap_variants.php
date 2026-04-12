<?php
define('BASEPATH', __DIR__ . '/application/');
require __DIR__ . '/application/config/app-config.php';
$m = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
$r=$m->query("SELECT email,mail_password FROM tblstaff WHERE staffid=1 LIMIT 1");
$staff=$r->fetch_assoc();
require __DIR__ . '/modules/mailbox/third_party/php-imap/Imap.php';
$tests = [
  ['imap.dreamhost.com','tls'],
  ['imap.dreamhost.com','ssl'],
  ['imap.dreamhost.com:143','tls'],
  ['imap.dreamhost.com:993','ssl'],
  ['imap.dreamhost.com:993','tls'],
  ['imap.dreamhost.com:143',''],
];
foreach($tests as $t){
  [$server,$enc]=$t;
  $imap = new Imap($server,$staff['email'],$staff['mail_password'],$enc);
  echo $server.' | '.$enc.' => '.($imap->isConnected()?'YES':'NO').' | '.($imap->isConnected()?'':$imap->getError()).PHP_EOL;
}
?>
