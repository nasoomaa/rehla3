<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbox_messages', function (Blueprint $table): void {
            $table->timestampTz('dead_lettered_at')->nullable()->index();
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->uuid('outbox_message_id')->nullable()->index();
            $table->string('channel')->default('in_app')->index();
            $table->unique(['user_id', 'outbox_message_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'outbox_message_id', 'channel']);
            $table->dropColumn(['outbox_message_id', 'channel']);
        });

        Schema::table('outbox_messages', function (Blueprint $table): void {
            $table->dropColumn('dead_lettered_at');
        });
    }
};
