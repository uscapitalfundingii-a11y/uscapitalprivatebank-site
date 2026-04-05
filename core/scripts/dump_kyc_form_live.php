<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$form = App\Models\Form::where('act', 'kyc')->first();

if (!$form) {
    echo "NO_FORM" . PHP_EOL;
    return;
}

echo json_encode($form->form_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
