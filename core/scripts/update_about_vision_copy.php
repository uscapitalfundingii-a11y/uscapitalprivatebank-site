<?php

use App\Models\Frontend;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$heading = 'Our Vision';
$body = 'Our vision is to establish US Capital Private Bank as a distinguished global private banking institution recognized for its commitment to financial privacy, sovereign structuring, asset protection, and enduring wealth preservation. We strive to provide a refined banking environment for private clients, trusts, institutions, and international account holders seeking a more strategic, confidential, and professionally governed alternative to conventional banking.';

$elements = Frontend::where('data_keys', 'about.element')->orderBy('id')->get();

foreach ($elements as $index => $element) {
    if ($index !== 1) {
        continue;
    }

    $values = (array) $element->data_values;

    if (array_key_exists('heading', $values)) {
        $values['heading'] = $heading;
    }

    if (array_key_exists('subheading', $values)) {
        $values['subheading'] = $body;
    }

    if (array_key_exists('description', $values)) {
        $values['description'] = $body;
    }

    $element->data_values = $values;
    $element->save();
    echo "Updated vision about.element row: {$element->id}\n";
}

echo "Done\n";
