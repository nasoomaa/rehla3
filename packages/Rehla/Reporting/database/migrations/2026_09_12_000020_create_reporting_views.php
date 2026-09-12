<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE OR REPLACE VIEW reporting_orders_summary AS
            SELECT 
                o.id AS order_id,
                o.account_id,
                o.service_id,
                o.traveler_id,
                o.amount_paid_minor,
                o.currency,
                o.financial_status,
                o.created_at,
                s.name_en AS service_name_en,
                s.name_ar AS service_name_ar
            FROM orders o
            LEFT JOIN order_service_snapshots s ON s.order_id = o.id
        ');

        DB::statement('
            CREATE OR REPLACE VIEW reporting_topups_summary AS
            SELECT 
                t.id AS topup_id,
                t.account_id,
                t.wallet_id,
                t.bank_account_id,
                t.amount_minor,
                t.status,
                t.submitted_at,
                t.decided_at,
                t.created_at,
                b.bank_name_en,
                b.bank_name_ar,
                b.account_number AS bank_account_number
            FROM topup_requests t
            LEFT JOIN company_bank_accounts b ON b.id = t.bank_account_id
        ');

        DB::statement('
            CREATE OR REPLACE VIEW reporting_executions_summary AS
            SELECT 
                e.id AS execution_id,
                e.order_id,
                e.account_id,
                e.traveler_id,
                e.service_id,
                e.status,
                e.created_at,
                e.last_status_at,
                EXTRACT(EPOCH FROM (e.last_status_at - e.created_at)) AS duration_seconds,
                (SELECT COUNT(*) FROM customer_action_requests r WHERE r.execution_id = e.id) AS action_requests_count
            FROM service_executions e
        ');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS reporting_executions_summary CASCADE');
        DB::statement('DROP VIEW IF EXISTS reporting_topups_summary CASCADE');
        DB::statement('DROP VIEW IF EXISTS reporting_orders_summary CASCADE');
    }
};
