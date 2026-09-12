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
                CREATE OR REPLACE FUNCTION prevent_audit_entries_mutation()
                RETURNS TRIGGER AS $$
                BEGIN
                    RAISE EXCEPTION 'audit_entries table is append-only: updates and deletes are prohibited';
                END;
                $$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS trg_protect_audit_entries_immutable ON audit_entries;
                CREATE TRIGGER trg_protect_audit_entries_immutable
                BEFORE UPDATE OR DELETE ON audit_entries
                FOR EACH ROW
                EXECUTE FUNCTION prevent_audit_entries_mutation();
            SQL);
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS trg_prevent_audit_entries_update
                BEFORE UPDATE ON audit_entries
                BEGIN
                    SELECT RAISE(ABORT, 'audit_entries table is append-only: updates and deletes are prohibited');
                END;

                CREATE TRIGGER IF NOT EXISTS trg_prevent_audit_entries_delete
                BEFORE DELETE ON audit_entries
                BEGIN
                    SELECT RAISE(ABORT, 'audit_entries table is append-only: updates and deletes are prohibited');
                END;
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP TRIGGER IF EXISTS trg_protect_audit_entries_immutable ON audit_entries;
                DROP FUNCTION IF EXISTS prevent_audit_entries_mutation();
            SQL);
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                DROP TRIGGER IF EXISTS trg_prevent_audit_entries_update;
                DROP TRIGGER IF EXISTS trg_prevent_audit_entries_delete;
            SQL);
        }
    }
};
