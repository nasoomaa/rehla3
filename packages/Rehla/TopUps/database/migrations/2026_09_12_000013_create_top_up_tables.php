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
        Schema::create('company_bank_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('bank_name_en');
            $table->string('bank_name_ar');
            $table->string('beneficiary_name');
            $table->string('account_number');
            $table->uuid('logo_document_id')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('topup_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id')->index();
            $table->uuid('wallet_id')->index();
            $table->foreignUuid('bank_account_id')->constrained('company_bank_accounts');
            $table->bigInteger('amount_minor');
            $table->string('transaction_reference');
            $table->string('normalized_reference', 100);
            $table->uuid('receipt_document_id')->index();
            $table->string('status', 30)->default('under_review')->index();
            $table->timestamp('submitted_at')->index();
            $table->uuid('reviewed_by')->nullable()->index();
            $table->timestamp('decided_at')->nullable()->index();
            $table->text('rejection_reason')->nullable();
            $table->uuid('credit_ledger_entry_id')->nullable();
            $table->timestamps();

            $table->unique(['bank_account_id', 'normalized_reference'], 'uniq_bank_norm_ref');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE topup_requests ADD CONSTRAINT chk_topup_min_amount CHECK (amount_minor >= 500000);');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('topup_requests');
        Schema::dropIfExists('company_bank_accounts');
    }
};
