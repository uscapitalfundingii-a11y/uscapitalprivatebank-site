<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignMissingUsernames extends Command
{
    protected $signature = 'users:assign-missing-usernames {--dry-run : Preview how many users would be updated}';

    protected $description = 'Assign system-generated usernames to users who do not have one';

    public function handle(): int
    {
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

        return self::SUCCESS;
    }
}
