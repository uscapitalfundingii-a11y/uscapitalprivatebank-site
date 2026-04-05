<?php

namespace App\Console\Commands;

use App\Constants\Status;
use App\Models\AccountOpeningRequest;
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
                $table->string('currency_code', 12)->default(gs('cur_text'));
                $table->string('currency_symbol', 20)->default(gs('cur_sym'));
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

        $updated = false;

        Schema::table('user_accounts', function (Blueprint $table) use (&$updated) {
            if (!Schema::hasColumn('user_accounts', 'currency_code')) {
                $table->string('currency_code', 12)->default(gs('cur_text'))->after('account_type');
                $updated = true;
            }

            if (!Schema::hasColumn('user_accounts', 'currency_symbol')) {
                $table->string('currency_symbol', 20)->default(gs('cur_sym'))->after('currency_code');
                $updated = true;
            }
        });

        if ($updated) {
            $this->info('Updated user_accounts table with currency columns.');
        }

        if (!Schema::hasTable('account_opening_requests')) {
            Schema::create('account_opening_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('account_type', 40)->default(AccountOpeningRequest::TYPE_MULTI_CURRENCY);
                $table->string('currency_code', 12);
                $table->string('currency_name', 120);
                $table->string('currency_symbol', 20)->nullable();
                $table->tinyInteger('status')->default(AccountOpeningRequest::STATUS_PENDING);
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['currency_code', 'status']);
            });

            $this->info('Created account_opening_requests table.');
        } else {
            $this->line('account_opening_requests table already exists.');
        }

        $requestTableUpdated = false;

        Schema::table('account_opening_requests', function (Blueprint $table) use (&$requestTableUpdated) {
            if (!Schema::hasColumn('account_opening_requests', 'account_type')) {
                $table->string('account_type', 40)->default(AccountOpeningRequest::TYPE_MULTI_CURRENCY)->after('user_id');
                $requestTableUpdated = true;
            }
        });

        if ($requestTableUpdated) {
            $this->info('Updated account_opening_requests table with account type column.');
        }

        $transactionTableUpdated = false;

        Schema::table('transactions', function (Blueprint $table) use (&$transactionTableUpdated) {
            if (!Schema::hasColumn('transactions', 'user_account_id')) {
                $table->unsignedBigInteger('user_account_id')->nullable()->after('user_id');
                $transactionTableUpdated = true;
            }

            if (!Schema::hasColumn('transactions', 'account_number')) {
                $table->string('account_number', 140)->nullable()->after('user_account_id');
                $transactionTableUpdated = true;
            }
        });

        if ($transactionTableUpdated) {
            $this->info('Updated transactions table with account tracking columns.');
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
                        'currency_code' => gs('cur_text'),
                        'currency_symbol' => gs('cur_sym'),
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
