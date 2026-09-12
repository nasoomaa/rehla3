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
        Schema::create('wallets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id')->unique();
            $table->string('currency', 3)->default('SDG');
            $table->bigInteger('balance_minor')->default(0);
            $table->bigInteger('lock_version')->default(1);
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('type', 20)->index();
            $table->bigInteger('amount_minor');
            $table->bigInteger('balance_after_minor');
            $table->string('reference_type', 50)->index();
            $table->string('reference_id', 100)->index();
            $table->string('idempotency_key', 100);
            $table->uuid('reverses_entry_id')->nullable()->index();

            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('metadata')->default('{}');
            } else {
                $table->json('metadata')->nullable();
            }

            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['wallet_id', 'idempotency_key'], 'uniq_wallet_idempotency');
            $table->unique(['wallet_id', 'reference_type', 'reference_id', 'type'], 'uniq_wallet_ref_type');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT chk_wallets_balance CHECK (balance_minor >= 0);');
            DB::statement("ALTER TABLE wallets ADD CONSTRAINT chk_wallets_currency CHECK (currency = 'SDG');");
            DB::statement('ALTER TABLE ledger_entries ADD CONSTRAINT chk_ledger_amount CHECK (amount_minor > 0);');
            DB::statement('ALTER TABLE ledger_entries ADD CONSTRAINT chk_ledger_balance_after CHECK (balance_after_minor >= 0);');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('wallets');
    }
};
