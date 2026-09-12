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
        Schema::create('services', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('short_description_en');
            $table->string('short_description_ar');
            $table->text('detailed_description_en');
            $table->text('detailed_description_ar');
            $table->string('expected_duration_en');
            $table->string('expected_duration_ar');
            $table->text('notes_en')->nullable();
            $table->text('notes_ar')->nullable();
            $table->unsignedBigInteger('current_price_minor');
            $table->string('currency', 3)->default('SDG');
            $table->unsignedInteger('price_version')->default(1);
            $table->string('status')->default('draft')->index();
            $table->integer('sort_order')->default(0)->index();

            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('requirements')->default('[]');
                $table->jsonb('media')->default('[]');
            } else {
                $table->json('requirements')->nullable();
                $table->json('media')->nullable();
            }

            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('service_price_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
            $table->unsignedBigInteger('price_minor');
            $table->string('currency', 3)->default('SDG');
            $table->unsignedInteger('version');
            $table->uuid('changed_by')->nullable()->index();
            $table->timestamp('effective_at')->index();
            $table->timestamp('created_at')->useCurrent();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE services ADD CONSTRAINT chk_services_price_minor CHECK (current_price_minor >= 0);');
            DB::statement("ALTER TABLE services ADD CONSTRAINT chk_services_currency CHECK (currency = 'SDG');");
            DB::statement('ALTER TABLE service_price_history ADD CONSTRAINT chk_price_history_minor CHECK (price_minor >= 0);');
            DB::statement("ALTER TABLE service_price_history ADD CONSTRAINT chk_price_history_currency CHECK (currency = 'SDG');");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_price_history');
        Schema::dropIfExists('services');
    }
};
