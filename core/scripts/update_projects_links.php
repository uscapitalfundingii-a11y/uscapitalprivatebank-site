<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$oldBase = 'https://projects.uscpb.net';
$newBase = 'https://www.uscapitalprivatebank.com/crm';

$replacements = [
    $oldBase . '/knowledge-base/' => $newBase . '/knowledge-base/',
    $oldBase . '/knowledge-base'  => $newBase . '/knowledge-base',
    $oldBase . '/'                => $newBase . '/',
    $oldBase                      => $newBase,
];

$tables = DB::select("
    SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND DATA_TYPE IN ('varchar', 'text', 'mediumtext', 'longtext')
    ORDER BY TABLE_NAME, COLUMN_NAME
");

$updated = [];

foreach ($tables as $column) {
    $table = $column->table_name;
    $field = $column->column_name;

    foreach ($replacements as $old => $new) {
        $sql = sprintf(
            "UPDATE `%s` SET `%s` = REPLACE(`%s`, ?, ?) WHERE `%s` LIKE ?",
            $table,
            $field,
            $field,
            $field
        );

        $count = DB::update($sql, [$old, $new, '%' . $old . '%']);

        if ($count > 0) {
            $updated[] = sprintf('%s.%s => %d', $table, $field, $count);
        }
    }
}

if (empty($updated)) {
    echo "No database references updated.\n";
    exit(0);
}

echo "Updated database references:\n";
foreach ($updated as $line) {
    echo $line . "\n";
}
