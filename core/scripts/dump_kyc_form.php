<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$form = App\Models\Form::where('act', 'kyc')->first();

if (!$form) {
    echo "NO_FORM" . PHP_EOL;
    exit(0);
}

foreach (($form->form_data ?? []) as $index => $field) {
    echo implode('|', [
        $index + 1,
        $field->name ?? '',
        $field->type ?? '',
        $field->label ?? '',
        $field->instruction ?? '',
    ]) . PHP_EOL;
}
