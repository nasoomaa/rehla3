<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION prevent_ledger_entries_mutation()
                RETURNS TRIGGER AS $$
                BEGIN
                    RAISE EXCEPTION 'ledger_entries table is append-only: updates and deletes are prohibited';
                END;
                $$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS trg_protect_ledger_entries_immutable ON ledger_entries;
                CREATE TRIGGER trg_protect_ledger_entries_immutable
                BEFORE UPDATE OR DELETE ON ledger_entries
                FOR EACH ROW
                EXECUTE FUNCTION prevent_ledger_entries_mutation();
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP TRIGGER IF EXISTS trg_protect_ledger_entries_immutable ON ledger_entries;
                DROP FUNCTION IF EXISTS prevent_ledger_entries_mutation();
            SQL);
        }
    }
};
