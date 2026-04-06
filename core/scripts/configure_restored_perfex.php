<?php

$path = '/home/dh_9a4ezr/uscapitalprivatebank.com/crm/application/config/app-config.php';

if (!file_exists($path)) {
    fwrite(STDERR, "app-config.php not found\n");
    exit(1);
}

$text = file_get_contents($path);

$replacements = [
    "define('APP_BASE_URL', 'https://projects.uscpb.net/');" => "define('APP_BASE_URL', 'https://www.uscapitalprivatebank.com/crm/');",
    "define('APP_DB_HOSTNAME', 'localhost');" => "define('APP_DB_HOSTNAME', 'crm.uscapitalprivatebank.com');",
    "define('APP_DB_USERNAME', 'uscpbp');" => "define('APP_DB_USERNAME', 'uscpbcrm');",
    "define('APP_DB_PASSWORD', 'Darius1985Angel!?.');" => "define('APP_DB_PASSWORD', '1995?+DM=blessing#$');",
    "define('APP_DB_NAME', 'uscpbp');" => "define('APP_DB_NAME', 'crmuscpb');",
];

$updated = strtr($text, $replacements);

if ($updated === $text) {
    echo "No replacements applied.\n";
}

file_put_contents($path, $updated);
echo "Configured restored Perfex app-config.php\n";
