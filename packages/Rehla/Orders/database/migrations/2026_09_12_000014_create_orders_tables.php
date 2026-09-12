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
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id')->index();
            $table->uuid('service_id');
            $table->uuid('traveler_id');
            $table->integer('price_minor');
            $table->integer('amount_paid_minor');
            $table->string('currency', 3)->default('SDG');
            $table->uuid('debit_ledger_entry_id')->unique();
            $table->string('financial_status')->default('paid');
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_positive_price CHECK (price_minor > 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_price_paid_match CHECK (price_minor = amount_paid_minor)');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_currency_sdg CHECK (currency = 'SDG')");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_financial_status_paid CHECK (financial_status = 'paid')");

        Schema::create('order_service_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->unique()->constrained('orders');
            $table->string('name_en');
            $table->string('name_ar');
            $table->jsonb('descriptions');
            $table->jsonb('requirements');
            $table->jsonb('expected_duration');
            $table->jsonb('notes');
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('order_traveler_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->unique()->constrained('orders');
            $table->string('full_name');
            $table->string('date_of_birth');
            $table->string('gender');
            $table->string('passport_number');
            $table->string('passport_issued_at')->nullable();
            $table->string('passport_expires_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('order_form_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->unique()->constrained('orders');
            $table->uuid('form_version_id');
            $table->integer('form_version');
            $table->string('form_checksum');
            $table->jsonb('schema');
            $table->jsonb('answers');
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_form_snapshots');
        Schema::dropIfExists('order_traveler_snapshots');
        Schema::dropIfExists('order_service_snapshots');
        Schema::dropIfExists('orders');
    }
};
