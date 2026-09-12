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
        Schema::create('audit_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_type');
            $table->uuid('actor_id')->nullable()->index();
            $table->string('action')->index();
            $table->string('subject_type')->index();
            $table->uuid('subject_id')->index();
            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('metadata')->default('{}');
            } else {
                $table->json('metadata');
            }
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestampTz('occurred_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_entries');
    }
};
