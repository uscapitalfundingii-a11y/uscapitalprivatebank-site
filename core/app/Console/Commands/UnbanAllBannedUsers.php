<?php

namespace App\Console\Commands;

use App\Constants\Status;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class UnbanAllBannedUsers extends Command
{
    protected $signature = 'users:unban-all-banned {--dry-run : Report how many accounts would be updated without changing them}';

    protected $description = 'Unban all banned user accounts while preserving their existing verification and KYC state';

    public function handle(): int
    {
        if (!Schema::hasTable('users')) {
            $this->warn('The configured database does not contain a users table, so there are no records to update.');
            return self::SUCCESS;
        }

        $count = User::banned()->count();

        if ($count === 0) {
            $this->info('There are no banned accounts to unban.');
            return self::SUCCESS;
        }

        $this->info("Found {$count} banned account(s).");
        $this->line('Unbanning will only set status to active and clear the ban reason.');
        $this->line('Email, mobile, and KYC flags will be left as-is so accounts return to their proper admin buckets.');

        if ($this->option('dry-run')) {
            $this->warn('Dry run only. No records were updated.');
            return self::SUCCESS;
        }

        $updated = User::banned()->update([
            'status' => Status::USER_ACTIVE,
            'ban_reason' => null,
        ]);

        $this->info("Unbanned {$updated} account(s).");

        return self::SUCCESS;
    }
}
