<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PurgeInvalidBannedUsers extends Command
{
    protected $signature = 'users:purge-invalid-banned {--dry-run : Report matches without deleting them}';

    protected $description = 'Delete banned user accounts that have invalid email addresses';

    public function handle(): int
    {
        if (!Schema::hasTable('users')) {
            $this->warn('The configured database does not contain a users table, so there are no records to scan.');
            return self::SUCCESS;
        }

        $matches = [];

        User::banned()
            ->select(['id', 'email', 'firstname', 'lastname', 'account_number'])
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$matches) {
                foreach ($users as $user) {
                    if ($this->isInvalidEmail($user->email)) {
                        $matches[] = $user;
                    }
                }
            });

        if (empty($matches)) {
            $this->info('No banned accounts with invalid email addresses were found.');
            return self::SUCCESS;
        }

        $rows = collect($matches)->map(function ($user) {
            return [
                'ID' => $user->id,
                'Account' => $user->account_number,
                'Name' => trim($user->firstname . ' ' . $user->lastname),
                'Email' => $user->email,
            ];
        })->all();

        $this->table(['ID', 'Account', 'Name', 'Email'], $rows);

        if ($this->option('dry-run')) {
            $this->warn('Dry run only. No records were deleted.');
            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($matches as $user) {
            $user->delete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} banned account(s) with invalid email addresses.");

        return self::SUCCESS;
    }

    protected function isInvalidEmail(?string $email): bool
    {
        $email = strtolower(trim((string) $email));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        $domain = substr(strrchr($email, '@'), 1);

        if (!$domain) {
            return true;
        }

        return !(checkdnsrr($domain, 'MX')
            || checkdnsrr($domain, 'A')
            || checkdnsrr($domain, 'AAAA'));
    }
}
