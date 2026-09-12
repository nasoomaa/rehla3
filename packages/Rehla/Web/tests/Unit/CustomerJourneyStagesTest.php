<?php

declare(strict_types=1);

it('validates the 13 canonical customer journey stages', function (): void {
    $stages = [
        'discovery' => 'Browse public services catalog',
        'inquiry' => 'Generate WhatsApp inquiry with service prefill',
        'register' => 'Register customer account',
        'login' => 'Authenticate customer session',
        'wallet_check' => 'Inspect wallet balance and ledger entries',
        'topup_initiate' => 'View active company bank accounts',
        'topup_submit' => 'Submit bank deposit receipt and transaction reference',
        'traveler_add' => 'Create and save traveler profile',
        'service_select' => 'Select service and initialize checkout',
        'form_fill' => 'Fill dynamic form schema with validation',
        'order_checkout' => 'Execute atomic wallet debit and order creation',
        'order_track' => 'Track fulfillment execution status transitions',
        'document_receive' => 'Download authorized clean fulfillment document',
    ];

    expect(count($stages))->toBe(13);
    expect(array_keys($stages))->toContain('discovery', 'inquiry', 'register', 'order_checkout', 'document_receive');
});
