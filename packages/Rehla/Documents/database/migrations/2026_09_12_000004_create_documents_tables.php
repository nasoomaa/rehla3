<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('owner_id')->index();
            $table->string('purpose');
            $table->timestamp('expires_at')->index();
            $table->timestamp('claimed_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('upload_session_id')->nullable()->constrained('upload_sessions')->nullOnDelete();
            $table->uuid('owner_id')->index();
            $table->string('purpose')->index();
            $table->string('disk')->default('private');
            $table->string('storage_key')->unique();
            $table->string('original_name');
            $table->string('detected_mime');
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->string('status')->default('pending_scan')->index();
            $table->string('rejection_code')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamp('attached_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('upload_sessions');
    }
};
