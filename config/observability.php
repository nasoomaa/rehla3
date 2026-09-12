<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Rehla Observability & Alerting Thresholds
    |--------------------------------------------------------------------------
    |
    | Production monitoring configuration defining operational metrics,
    | alert triggers, and distributed tracing instrumentation.
    |
    */

    'tracing' => [
        'header' => 'X-Trace-Id',
        'log_context' => true,
    ],

    'thresholds' => [
        // Financial integrity: Any balance or ledger mismatch requires immediate P0 page
        'wallet_reconciliation_mismatch_max' => 0,

        // Transactional Outbox: Event delivery age max 5 minutes (300 seconds)
        'oldest_outbox_age_seconds_max' => 300,

        // Dead letter queue: Any permanently unprocessable message requires P1 review
        'dead_letters_max' => 0,

        // HTTP Error Budget: 5xx errors exceeding 2% over 5-minute rolling window
        'http_5xx_error_rate_percentage_max' => 2.0,

        // Database Concurrency: Maximum transaction retries under contention
        'db_transaction_retries_max' => 3,

        // Document Security: Any malware or corrupted upload triggers security alert
        'document_scan_failures_alert' => true,
    ],

    'processes' => [
        'web' => [
            'type' => 'http',
            'health_liveness' => '/up',
            'health_readiness' => '/ready',
        ],
        'worker' => [
            'type' => 'queue',
            'command' => 'queue:work --timeout=90 --tries=1',
            'retry_after' => 120, // Must be strictly greater than queue worker timeout
        ],
        'scheduler' => [
            'type' => 'cron',
            'command' => 'schedule:run',
            'interval' => '* * * * *',
            'singleton' => true,
        ],
    ],
];
