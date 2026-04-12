<?php
define('BASEPATH', __DIR__ . '/application/');
require __DIR__ . '/application/config/app-config.php';
$m = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
function opt($m,$name){ $n=$m->real_escape_string($name); $r=$m->query("SELECT value FROM tbloptions WHERE name='$n' LIMIT 1"); $row=$r?$r->fetch_assoc():null; return $row?$row['value']:null; }
$r=$m->query("SELECT email,mail_password FROM tblstaff WHERE staffid=1 LIMIT 1");
$staff=$r->fetch_assoc();
require __DIR__ . '/modules/mailbox/third_party/php-imap/Imap.php';
$imap = new Imap(opt($m,'mailbox_imap_server'), $staff['email'], $staff['mail_password'], opt($m,'mailbox_encryption'));
echo 'CONNECTED=' . ($imap->isConnected() ? 'YES' : 'NO') . PHP_EOL;
if ($imap->isConnected()) {
  $folder = opt($m,'mailbox_folder_scan') ?: 'Inbox';
  $imap->selectFolder($folder);
  $unread = $imap->getUnreadMessages();
  $all = $imap->getMessages(false);
  echo 'FOLDER=' . $folder . PHP_EOL;
  echo 'UNREAD=' . count($unread) . PHP_EOL;
  echo 'TOTAL=' . count($all) . PHP_EOL;
  if (!empty($unread)) {
    foreach (array_slice($unread,0,3) as $msg) {
      echo json_encode(['uid'=>$msg['uid'],'from'=>$msg['from'],'subject'=>$msg['subject']], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
  }
} else {
  echo 'ERROR=' . $imap->getError() . PHP_EOL;
}
?>
