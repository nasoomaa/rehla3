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
        Schema::create('form_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('service_id')->index();
            $table->unsignedInteger('version')->default(0);

            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('schema')->default('[]');
            } else {
                $table->json('schema')->nullable();
            }

            $table->string('checksum', 64)->nullable();
            $table->string('status')->default('draft')->index();
            $table->uuid('published_by')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['service_id', 'status', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_versions');
    }
};
