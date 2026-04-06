<?php

$mysqli = new mysqli('crm.uscapitalprivatebank.com', 'uscpbcrm', '1995?+DM=blessing#$', 'crmuscpb');

if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
    exit(1);
}

$checks = [
    'tblstaff',
    'tblclients',
    'tblcontacts',
    'tblinvoices',
    'tblprojects',
    'tbltasks',
    'tblknowledge_base',
    'tblknowledge_base_groups',
    'tbltickets',
    'tbltickets_predefined_replies',
    'tblannouncements',
];

foreach ($checks as $table) {
    $result = $mysqli->query("SELECT COUNT(*) AS c FROM {$table}");
    if (!$result) {
        echo "{$table}: ERROR {$mysqli->error}\n";
        continue;
    }

    $row = $result->fetch_assoc();
    echo "{$table}: {$row['c']}\n";
}

$staff = $mysqli->query("SELECT staffid, firstname, lastname, email, admin, active FROM tblstaff ORDER BY staffid LIMIT 10");
if ($staff) {
    echo "---staff-sample---\n";
    while ($row = $staff->fetch_assoc()) {
        echo implode(' | ', [$row['staffid'], $row['firstname'], $row['lastname'], $row['email'], $row['admin'], $row['active']]) . "\n";
    }
}
