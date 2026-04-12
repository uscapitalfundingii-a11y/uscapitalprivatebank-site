<?php
define('BASEPATH', __DIR__ . '/application/');
require __DIR__ . '/application/config/app-config.php';
$m = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($m->connect_error) { echo 'CONNECT_ERR: ' . $m->connect_error . PHP_EOL; exit(1); }
$queries = [
  "SELECT name,value FROM tbloptions WHERE name IN ('mailbox_enabled','mailbox_imap_server','mailbox_encryption','mailbox_folder_scan','mailbox_check_every','mailbox_only_loop_on_unseen_emails') ORDER BY name",
  "SELECT staffid,firstname,lastname,email,CASE WHEN mail_password IS NULL OR mail_password='' THEN 'EMPTY' ELSE CONCAT('SET:',CHAR_LENGTH(mail_password)) END AS mail_password_state,last_email_check FROM tblstaff ORDER BY staffid LIMIT 20",
  "SELECT COUNT(*) AS inbox_count FROM tblmail_inbox",
  "SELECT COUNT(*) AS outbox_count FROM tblmail_outbox"
];
foreach ($queries as $q) {
  echo "--QUERY--\n$q\n";
  $r = $m->query($q);
  if (!$r) { echo 'ERR: ' . $m->error . PHP_EOL; continue; }
  while ($row = $r->fetch_assoc()) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL;
  }
}
?>
