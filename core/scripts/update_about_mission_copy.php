<?php

use App\Models\Frontend;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$heading = 'Our Mission';
$body = 'Our mission is to cultivate, protect, and advance long-term generational wealth through trusted private banking relationships, strategic financial stewardship, and professionally structured banking solutions. We are committed to serving our clients with discretion, integrity, and institutional strength—providing a private financial environment designed to preserve legacy, support sovereignty, and empower enduring wealth across generations.';

$contents = Frontend::where('data_keys', 'about.content')->get();

foreach ($contents as $content) {
    $values = (array) $content->data_values;

    if (array_key_exists('title', $values)) {
        $values['title'] = $heading;
    }

    if (array_key_exists('heading', $values)) {
        $values['heading'] = $heading;
    }

    if (array_key_exists('subheading', $values)) {
        $values['subheading'] = $body;
    }

    $content->data_values = $values;
    $content->save();
    echo "Updated about.content for template: {$content->tempname}\n";
}

$elements = Frontend::where('data_keys', 'about.element')->orderBy('id')->get();

foreach ($elements as $index => $element) {
    if ($index > 0) {
        break;
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
    echo "Updated first about.element row: {$element->id}\n";
}

echo "Done\n";
