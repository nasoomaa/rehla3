<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('idempotency_key', 128);
            $table->string('request_fingerprint', 64);
            $table->string('status')->default('in_progress');
            $table->uuid('order_id')->nullable();
            $table->integer('response_status')->nullable();
            $table->jsonb('response_body')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('completed_at')->nullable();

            $table->unique(['account_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_attempts');
    }
};
