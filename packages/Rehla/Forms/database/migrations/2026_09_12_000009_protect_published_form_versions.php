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
                CREATE OR REPLACE FUNCTION prevent_published_form_version_mutation()
                RETURNS TRIGGER AS $$
                BEGIN
                    IF OLD.status = 'published' THEN
                        RAISE EXCEPTION 'Published form version is immutable: updates and deletes are prohibited';
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS trg_protect_published_form_versions ON form_versions;
                CREATE TRIGGER trg_protect_published_form_versions
                BEFORE UPDATE OR DELETE ON form_versions
                FOR EACH ROW
                EXECUTE FUNCTION prevent_published_form_version_mutation();
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP TRIGGER IF EXISTS trg_protect_published_form_versions ON form_versions;
                DROP FUNCTION IF EXISTS prevent_published_form_version_mutation();
            SQL);
        }
    }
};
