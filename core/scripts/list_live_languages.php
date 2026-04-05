<?php

$pdo = new PDO(
    'mysql:host=cacciotti.iad1-mysql-e2-5b.dreamhost.com;port=3306;dbname=uscpb',
    'uscpb',
    'Darius1985Angel!?.'
);

foreach ($pdo->query('SELECT id, name, code, is_default FROM languages ORDER BY id') as $row) {
    echo $row['id'] . '|' . $row['name'] . '|' . $row['code'] . '|' . $row['is_default'] . PHP_EOL;
}
