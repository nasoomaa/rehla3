<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_name')->index();
            $table->string('aggregate_type')->index();
            $table->string('aggregate_id')->index();
            $table->unsignedInteger('payload_version')->default(1);
            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('payload');
            } else {
                $table->json('payload');
            }
            $table->string('deduplication_key')->unique();
            $table->timestampTz('available_at')->useCurrent()->index();
            $table->timestampTz('locked_at')->nullable()->index();
            $table->string('locked_by')->nullable()->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('delivered_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('type')->index();
            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('payload');
            } else {
                $table->json('payload');
            }
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('outbox_messages');
    }
};
