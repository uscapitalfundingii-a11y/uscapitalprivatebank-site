<?php

use App\Models\Frontend;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$heading = 'US Capital Private Bank';
$subheading = 'Where Private Wealth Meets Sovereign Financial Freedom' . PHP_EOL . PHP_EOL .
    'US Capital Private Bank offers a refined private banking environment for clients seeking financial privacy, strategic asset protection, international banking flexibility, and sovereign-level financial structuring. Designed for private individuals, trustees, entrepreneurs, institutions, and legacy-focused families, our banking platform serves those who require more than conventional banking can provide.';

$banners = Frontend::where('data_keys', 'banner.content')->get();

foreach ($banners as $banner) {
    $values = (array) $banner->data_values;
    $values['heading'] = $heading;
    $values['subheading'] = $subheading;
    $banner->data_values = $values;
    $banner->save();
    echo "Updated banner for template: {$banner->tempname}\n";
}

echo "Done\n";
