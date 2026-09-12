<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION protect_orders_immutability()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Order and snapshot records are immutable and cannot be modified or deleted';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER protect_orders_trigger
            BEFORE UPDATE OR DELETE ON orders
            FOR EACH ROW
            EXECUTE FUNCTION protect_orders_immutability();

            CREATE TRIGGER protect_order_service_snapshots_trigger
            BEFORE UPDATE OR DELETE ON order_service_snapshots
            FOR EACH ROW
            EXECUTE FUNCTION protect_orders_immutability();

            CREATE TRIGGER protect_order_traveler_snapshots_trigger
            BEFORE UPDATE OR DELETE ON order_traveler_snapshots
            FOR EACH ROW
            EXECUTE FUNCTION protect_orders_immutability();

            CREATE TRIGGER protect_order_form_snapshots_trigger
            BEFORE UPDATE OR DELETE ON order_form_snapshots
            FOR EACH ROW
            EXECUTE FUNCTION protect_orders_immutability();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS protect_order_form_snapshots_trigger ON order_form_snapshots;
            DROP TRIGGER IF EXISTS protect_order_traveler_snapshots_trigger ON order_traveler_snapshots;
            DROP TRIGGER IF EXISTS protect_order_service_snapshots_trigger ON order_service_snapshots;
            DROP TRIGGER IF EXISTS protect_orders_trigger ON orders;
            DROP FUNCTION IF EXISTS protect_orders_immutability();
        SQL);
    }
};
