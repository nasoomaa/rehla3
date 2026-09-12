<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION protect_execution_history_immutability()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Execution history and internal notes are immutable and cannot be updated or deleted';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER protect_execution_status_history_trigger
            BEFORE UPDATE OR DELETE ON execution_status_history
            FOR EACH ROW
            EXECUTE FUNCTION protect_execution_history_immutability();

            CREATE TRIGGER protect_execution_internal_notes_trigger
            BEFORE UPDATE OR DELETE ON execution_internal_notes
            FOR EACH ROW
            EXECUTE FUNCTION protect_execution_history_immutability();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS protect_execution_internal_notes_trigger ON execution_internal_notes;
            DROP TRIGGER IF EXISTS protect_execution_status_history_trigger ON execution_status_history;
            DROP FUNCTION IF EXISTS protect_execution_history_immutability();
        SQL);
    }
};
