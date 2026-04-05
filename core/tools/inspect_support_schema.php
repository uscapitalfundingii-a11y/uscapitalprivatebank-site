<?php

$env = parse_ini_file(__DIR__ . '/../.env');

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        $env['DB_HOST'],
        $env['DB_PORT'],
        $env['DB_DATABASE']
    ),
    $env['DB_USERNAME'],
    $env['DB_PASSWORD']
);

foreach (['support_messages', 'support_tickets', 'admins'] as $table) {
    echo $table . PHP_EOL;
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");

    foreach ($stmt as $row) {
        printf(
            "%s:%s:%s:%s\n",
            $row['Field'],
            $row['Type'],
            $row['Null'],
            $row['Default'] ?? 'NULL'
        );
    }

    echo PHP_EOL;
}
