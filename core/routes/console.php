<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('users:assign-missing-usernames {--dry-run}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $updated = 0;

    User::query()
        ->where(function ($query) {
            $query->whereNull('username')
                ->orWhereRaw("TRIM(COALESCE(username, '')) = ''");
        })
        ->orderBy('id')
        ->chunkById(200, function ($users) use ($dryRun, &$updated) {
            foreach ($users as $user) {
                $username = $user->generateSystemUsername();

                if ($dryRun) {
                    $updated++;
                    $this->line("Would assign {$username} to user #{$user->id}");
                    continue;
                }

                $user->forceFill(['username' => $username])->saveQuietly();
                $updated++;
                $this->line("Assigned {$username} to user #{$user->id}");
            }
        });

    $message = $dryRun
        ? "Dry run complete. {$updated} users are missing usernames."
        : "Username backfill complete. Updated {$updated} users.";

    $this->info($message);
})->purpose('Assign system-generated usernames to users who do not have one');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
