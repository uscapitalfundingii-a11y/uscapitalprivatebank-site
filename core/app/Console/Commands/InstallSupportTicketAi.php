<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InstallSupportTicketAi extends Command
{
    protected $signature = 'tickets:install-ai-assistant';

    protected $description = 'Add AI support reply columns to the support_messages table';

    public function handle(): int
    {
        if (!Schema::hasTable('support_messages')) {
            $this->error('support_messages table does not exist.');
            return self::FAILURE;
        }

        $updated = false;

        Schema::table('support_messages', function (Blueprint $table) use (&$updated) {
            if (!Schema::hasColumn('support_messages', 'is_ai_response')) {
                $table->tinyInteger('is_ai_response')->default(0)->after('admin_id');
                $updated = true;
            }

            if (!Schema::hasColumn('support_messages', 'ai_reply_to_message_id')) {
                $table->unsignedBigInteger('ai_reply_to_message_id')->nullable()->after('is_ai_response');
                $table->index('ai_reply_to_message_id');
                $updated = true;
            }

            if (!Schema::hasColumn('support_messages', 'ai_model')) {
                $table->string('ai_model', 80)->nullable()->after('ai_reply_to_message_id');
                $updated = true;
            }
        });

        if ($updated) {
            $this->info('AI support assistant columns installed successfully.');
            return self::SUCCESS;
        }

        $this->line('AI support assistant columns already exist.');

        return self::SUCCESS;
    }
}
