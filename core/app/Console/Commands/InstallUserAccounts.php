<?php

namespace App\Console\Commands;

use App\Constants\Status;
use App\Models\User;
use App\Models\UserAccount;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InstallUserAccounts extends Command
{
    protected $signature = 'accounts:install-multi-account';

    protected $description = 'Create the user_accounts table and backfill existing users into primary checking accounts';

    public function handle(): int
    {
        if (!Schema::hasTable('user_accounts')) {
            Schema::create('user_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('account_number', 140)->unique();
                $table->string('account_name', 140)->nullable();
                $table->string('account_type', 40)->default('checking');
                $table->decimal('balance', 28, 8)->default(0);
                $table->tinyInteger('status')->default(Status::ENABLE);
                $table->tinyInteger('is_primary')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'is_primary']);
                $table->index(['user_id', 'account_type']);
            });

            $this->info('Created user_accounts table.');
        } else {
            $this->line('user_accounts table already exists.');
        }

        $created = 0;
        $updated = 0;

        User::query()->orderBy('id')->chunkById(200, function ($users) use (&$created, &$updated) {
            foreach ($users as $user) {
                if (!$user->account_number) {
                    continue;
                }

                $account = UserAccount::updateOrCreate(
                    ['account_number' => $user->account_number],
                    [
                        'user_id' => $user->id,
                        'account_name' => 'Primary Checking',
                        'account_type' => 'checking',
                        'balance' => $user->balance,
                        'status' => $user->status == Status::USER_BAN ? Status::DISABLE : Status::ENABLE,
                        'is_primary' => 1,
                    ]
                );

                if ($account->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                UserAccount::where('user_id', $user->id)
                    ->where('id', '!=', $account->id)
                    ->where('is_primary', 1)
                    ->update(['is_primary' => 0]);
            }
        });

        $this->info("Backfill complete. Created {$created} accounts, updated {$updated} accounts.");

        return self::SUCCESS;
    }
}
