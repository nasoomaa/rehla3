<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Catalog\Actions\ChangeServicePrice;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Contracts\ServiceCatalog;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Catalog\Exceptions\InvalidPriceException;
use Rehla\Catalog\Queries\GetServicePriceHistory;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('tracks price history atomically, increments price_version, and records audit entry', function (): void {
    $createAction = app(CreateService::class);
    $publishAction = app(PublishService::class);
    $changePriceAction = app(ChangeServicePrice::class);
    $catalogContract = app(ServiceCatalog::class);
    $historyQuery = app(GetServicePriceHistory::class);

    $actorId = (string) Str::uuid();

    $service = $createAction->execute(new CreateServiceData(
        slug: 'medical-visa-'.Str::random(6),
        nameEn: 'Medical Visa Service',
        nameAr: 'تأشيرة العلاج',
        shortDescriptionEn: 'Medical travel assistance',
        shortDescriptionAr: 'مساعدة السفر للعلاج',
        detailedDescriptionEn: 'Full support for medical visa issuance.',
        detailedDescriptionAr: 'دعم كامل لإصدار تأشيرة العلاج وتجهيز التقارير الطبية.',
        expectedDurationEn: '3 days',
        expectedDurationAr: '٣ أيام',
        priceMinor: 25_000_00,
        requirements: [['text_en' => 'Medical report', 'text_ar' => 'تقرير طبي', 'sort_order' => 1]],
        media: [['document_id' => (string) Str::uuid(), 'alt_en' => 'Banner', 'alt_ar' => 'بانر', 'sort_order' => 1]],
    ), actorId: $actorId);

    $publishAction->execute($service->id, actorId: $actorId);

    // Initial quote
    $quote1 = $catalogContract->currentQuote($service->id);
    expect($quote1->priceMinor)->toBe(25_000_00)
        ->and($quote1->quoteVersion)->toBe(1)
        ->and($quote1->currency)->toBe('SDG');

    // Change price
    $newActorId = (string) Str::uuid();
    $changePriceAction->execute(
        serviceId: $service->id,
        newPriceMinor: 30_000_00,
        actorId: $newActorId
    );

    // Verify quote updated
    $quote2 = $catalogContract->currentQuote($service->id);
    expect($quote2->priceMinor)->toBe(30_000_00)
        ->and($quote2->quoteVersion)->toBe(2);

    // Verify price history count and versions
    $history = $historyQuery->execute($service->id);
    expect($history)->toHaveCount(2)
        ->and($history[0]->version)->toBe(1)
        ->and($history[0]->priceMinor)->toBe(25_000_00)
        ->and($history[1]->version)->toBe(2)
        ->and($history[1]->priceMinor)->toBe(30_000_00)
        ->and($history[1]->changedBy)->toBe($newActorId);

    // Verify audit log exists
    $auditCount = DB::table('audit_entries')
        ->where('subject_type', 'service')
        ->where('subject_id', $service->id)
        ->where('action', 'service.price_changed')
        ->count();

    expect($auditCount)->toBeGreaterThanOrEqual(1);
});

it('rejects negative prices', function (): void {
    $createAction = app(CreateService::class);
    $changePriceAction = app(ChangeServicePrice::class);

    $service = $createAction->execute(new CreateServiceData(
        slug: 'tourist-visa-'.Str::random(6),
        nameEn: 'Tourist Visa',
        nameAr: 'تأشيرة سياحة',
        shortDescriptionEn: 'Tourism',
        shortDescriptionAr: 'سياحة',
        detailedDescriptionEn: 'Detailed tourist',
        detailedDescriptionAr: 'مفصل سياحة',
        expectedDurationEn: '2 days',
        expectedDurationAr: 'يومان',
        priceMinor: 10_000_00,
        requirements: [['text_en' => 'Passport', 'text_ar' => 'جواز', 'sort_order' => 1]],
        media: [['document_id' => (string) Str::uuid(), 'alt_en' => 'Img', 'alt_ar' => 'صورة', 'sort_order' => 1]],
    ));

    expect(fn () => $changePriceAction->execute($service->id, -5_000_00, (string) Str::uuid()))
        ->toThrow(InvalidPriceException::class);
});
