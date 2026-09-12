<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Rehla\Integrations\Contracts\InquiryLinkBuilder;

function businessTableCounts(): array
{
    return [
        'orders' => DB::table('orders')->count(),
        'notifications' => DB::table('notifications')->count(),
        'outbox_messages' => DB::table('outbox_messages')->count(),
        'audit_entries' => DB::table('audit_entries')->count(),
    ];
}

it('generates deep link with inquiry prefilled text', function (): void {
    Config::set('rehla-integrations.whatsapp_number', '+249 91 234 5678');

    $before = businessTableCounts();

    /** @var InquiryLinkBuilder $builder */
    $builder = app(InquiryLinkBuilder::class);

    // English inquiry
    $linkEn = $builder->forService('UAE Visa', 'en');
    expect($linkEn->url)->toStartWith('https://wa.me/249912345678?text=')
        ->and(rawurldecode($linkEn->url))->toContain('Hello Rehla, I would like to inquire about UAE Visa.')
        ->and($linkEn->text)->toBe('Hello Rehla, I would like to inquire about UAE Visa.')
        ->and($linkEn->locale)->toBe('en');

    // Arabic inquiry
    $linkAr = $builder->forService('تأشيرة دبي', 'ar');
    expect($linkAr->url)->toStartWith('https://wa.me/249912345678?text=')
        ->and(rawurldecode($linkAr->url))->toContain('مرحبًا رحلة، أود الاستفسار عن خدمة تأشيرة دبي.')
        ->and($linkAr->text)->toBe('مرحبًا رحلة، أود الاستفسار عن خدمة تأشيرة دبي.')
        ->and($linkAr->locale)->toBe('ar');

    // Zero database writes
    expect(businessTableCounts())->toBe($before);
});

it('sanitizes messy phone numbers and preserves rawurlencoding', function (): void {
    Config::set('rehla-integrations.whatsapp_number', '+249-(912)-34-5678 ext. 99');

    /** @var InquiryLinkBuilder $builder */
    $builder = app(InquiryLinkBuilder::class);
    $link = $builder->forService('Flight Ticket', 'en');

    // Strips all non-digit characters
    expect($link->url)->toStartWith('https://wa.me/24991234567899?text=');

    // Raw URL encode uses %20 for spaces
    expect($link->url)->toContain('Hello%20Rehla%2C%20I%20would%20like%20to%20inquire%20about%20Flight%20Ticket.');
});
