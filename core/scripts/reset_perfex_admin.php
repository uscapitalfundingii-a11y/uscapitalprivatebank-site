<?php

$host = 'crm.uscapitalprivatebank.com';
$database = 'crmuscpb';
$username = 'uscpbcrm';
$password = '1995?+DM=blessing#$';

$adminEmail = 'admin@crm.uscapitalprivatebank.com';
$newPassword = 'WelcomeCRM2026!';
$fallbackFirstName = 'CRM';
$fallbackLastName = 'Admin';

$mysqli = new mysqli($host, $username, $password, $database);

if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
    exit(1);
}

$result = $mysqli->query("SELECT staffid, email, admin FROM tblstaff WHERE admin = 1 ORDER BY staffid ASC LIMIT 1");

if (!$result) {
    fwrite(STDERR, "Query failed: {$mysqli->error}\n");
    exit(1);
}

$admin = $result->fetch_assoc();

if (!$admin) {
    $insert = $mysqli->prepare("INSERT INTO tblstaff (firstname, lastname, email, password, admin, active, datecreated) VALUES (?, ?, ?, ?, 1, 1, NOW())");
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $insert->bind_param('ssss', $fallbackFirstName, $fallbackLastName, $adminEmail, $hash);

    if (!$insert->execute()) {
        fwrite(STDERR, "Create admin failed: {$insert->error}\n");
        exit(1);
    }

    $staffId = $insert->insert_id;
    echo "created|{$staffId}|{$adminEmail}\n";
    exit(0);
}

$staffId = (int) $admin['staffid'];
$targetEmail = $admin['email'] ?: $adminEmail;
$hash = password_hash($newPassword, PASSWORD_BCRYPT);

$update = $mysqli->prepare("UPDATE tblstaff SET email = ?, password = ?, active = 1 WHERE staffid = ?");
$update->bind_param('ssi', $targetEmail, $hash, $staffId);

if (!$update->execute()) {
    fwrite(STDERR, "Update admin failed: {$update->error}\n");
    exit(1);
}

echo "updated|{$staffId}|{$targetEmail}\n";
