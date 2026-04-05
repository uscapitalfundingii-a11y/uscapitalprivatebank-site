<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InstallKycDocumentLibrary extends Command
{
    protected $signature = 'kyc:install-document-library';

    protected $description = 'Install the KYC document library table';

    public function handle(): int
    {
        if (!Schema::hasTable('kyc_documents')) {
            Schema::create('kyc_documents', function (Blueprint $table) {
                $table->id();
                $table->string('field_label')->unique();
                $table->string('field_name');
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('stored_name');
                $table->string('original_name')->nullable();
                $table->string('extension', 20);
                $table->string('mime_type')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });

            $this->info('Created table: kyc_documents');
        } else {
            $this->info('Table already exists: kyc_documents');
        }

        return self::SUCCESS;
    }
}
