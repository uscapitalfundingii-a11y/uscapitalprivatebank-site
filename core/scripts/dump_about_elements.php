<?php

use App\Models\Frontend;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$elements = Frontend::where('data_keys', 'about.element')->orderBy('id')->get();

foreach ($elements as $element) {
    $values = (array) $element->data_values;
    $heading = $values['heading'] ?? '';
    $description = $values['description'] ?? ($values['subheading'] ?? '');
    $preview = preg_replace('/\s+/', ' ', trim((string) $description));
    $preview = mb_substr($preview, 0, 180);

    echo sprintf(
        "[%d] %s | %s | %s\n",
        $element->id,
        $element->tempname,
        $heading,
        $preview
    );
}
