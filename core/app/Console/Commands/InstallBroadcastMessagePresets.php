<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InstallBroadcastMessagePresets extends Command
{
    protected $signature = 'notifications:install-broadcast-presets';

    protected $description = 'Create the broadcast_message_presets table for saved bulk notification messages';

    public function handle(): int
    {
        if (!Schema::hasTable('broadcast_message_presets')) {
            Schema::create('broadcast_message_presets', function (Blueprint $table) {
                $table->id();
                $table->string('name', 160);
                $table->string('via', 20)->default('email');
                $table->string('audience_key', 120)->nullable();
                $table->string('audience_label', 255)->nullable();
                $table->string('subject', 255)->nullable();
                $table->longText('message');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                $table->index(['via', 'last_used_at']);
            });

            $this->info('Broadcast message preset table installed successfully.');

            return self::SUCCESS;
        }

        $updated = false;

        Schema::table('broadcast_message_presets', function (Blueprint $table) use (&$updated) {
            if (!Schema::hasColumn('broadcast_message_presets', 'audience_key')) {
                $table->string('audience_key', 120)->nullable()->after('via');
                $updated = true;
            }

            if (!Schema::hasColumn('broadcast_message_presets', 'audience_label')) {
                $table->string('audience_label', 255)->nullable()->after('audience_key');
                $updated = true;
            }
        });

        $this->line($updated ? 'Broadcast message preset table updated successfully.' : 'broadcast_message_presets table already exists.');

        return self::SUCCESS;
    }
}
