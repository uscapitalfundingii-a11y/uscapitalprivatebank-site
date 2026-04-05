<?php

$basePath = is_file(__DIR__ . '/vendor/autoload.php') ? __DIR__ : dirname(__DIR__);

require $basePath . '/vendor/autoload.php';

$app = require $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$query = App\Models\User::query()
    ->where(function ($builder) {
        $builder->whereNull('username')
            ->orWhereRaw("TRIM(COALESCE(username, '')) = ''");
    })
    ->orderBy('id');

$count = (clone $query)->count();
echo "Missing usernames: {$count}\n";

$updated = 0;

$query->chunkById(200, function ($users) use (&$updated) {
    foreach ($users as $user) {
        $user->forceFill([
            'username' => $user->generateSystemUsername(),
        ])->saveQuietly();

        $updated++;
        echo "Assigned {$user->username} to user #{$user->id}\n";
    }
});

echo "Updated users: {$updated}\n";
