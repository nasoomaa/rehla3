<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\DeactivateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Actions\ReorderServices;
use Rehla\Catalog\Contracts\ServiceCatalog;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Catalog\Enums\ServiceStatus;
use Rehla\Catalog\Exceptions\ServicePublishingValidationException;
use Rehla\Catalog\Queries\ListPublishedServices;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('creates a service in draft status and can transition to published', function (): void {
    $createAction = app(CreateService::class);
    $publishAction = app(PublishService::class);

    $serviceData = new CreateServiceData(
        slug: 'umrah-visa-'.Str::random(6),
        nameEn: 'Umrah Visa Service',
        nameAr: 'تأشيرة العمرة',
        shortDescriptionEn: 'Fast visa processing',
        shortDescriptionAr: 'معالجة سريعة للتأشيرة',
        detailedDescriptionEn: 'Full support for Umrah visa issuance and documentation.',
        detailedDescriptionAr: 'دعم كامل لإصدار تأشيرة العمرة وتجهيز المستندات.',
        expectedDurationEn: '3-5 business days',
        expectedDurationAr: '٣-٥ أيام عمل',
        priceMinor: 50_000_00,
        requirements: [
            ['text_en' => 'Valid passport with 6 months validity', 'text_ar' => 'جواز سفر ساري لمدة 6 أشهر على الأقل', 'sort_order' => 1],
        ],
        media: [
            ['document_id' => (string) Str::uuid(), 'alt_en' => 'Service banner', 'alt_ar' => 'بانر الخدمة', 'sort_order' => 1],
        ],
    );

    $created = $createAction->execute($serviceData, actorId: (string) Str::uuid());

    expect($created->status)->toBe(ServiceStatus::Draft)
        ->and($created->currentPriceMinor)->toBe(50_000_00)
        ->and($created->currency)->toBe('SDG')
        ->and($created->priceVersion)->toBe(1);

    $published = $publishAction->execute($created->id, actorId: (string) Str::uuid());

    expect($published->status)->toBe(ServiceStatus::Published)
        ->and($published->publishedAt)->not->toBeNull();
});

it('blocks publishing a service missing requirements or descriptions', function (): void {
    $createAction = app(CreateService::class);
    $publishAction = app(PublishService::class);

    $incomplete = $createAction->execute(new CreateServiceData(
        slug: 'incomplete-service-'.Str::random(6),
        nameEn: 'Incomplete Service',
        nameAr: '', // Missing Arabic name
        shortDescriptionEn: 'Short',
        shortDescriptionAr: 'قصير',
        detailedDescriptionEn: 'Detailed',
        detailedDescriptionAr: 'مفصل',
        expectedDurationEn: '1 day',
        expectedDurationAr: 'يوم',
        priceMinor: 10_000_00,
        requirements: [],
        media: [],
    ));

    expect(fn () => $publishAction->execute($incomplete->id))
        ->toThrow(ServicePublishingValidationException::class);
});

it('deactivates a service and marks current quote as unavailable', function (): void {
    $createAction = app(CreateService::class);
    $publishAction = app(PublishService::class);
    $deactivateAction = app(DeactivateService::class);
    $catalogContract = app(ServiceCatalog::class);

    $service = $createAction->execute(new CreateServiceData(
        slug: 'transit-visa-'.Str::random(6),
        nameEn: 'Transit Visa',
        nameAr: 'تأشيرة ترانزيت',
        shortDescriptionEn: 'Quick transit',
        shortDescriptionAr: 'ترانزيت سريع',
        detailedDescriptionEn: 'Transit visa processing',
        detailedDescriptionAr: 'معالجة تأشيرة الترانزيت',
        expectedDurationEn: '2 days',
        expectedDurationAr: 'يومان',
        priceMinor: 20_000_00,
        requirements: [['text_en' => 'Flight ticket', 'text_ar' => 'تذكرة طيران', 'sort_order' => 1]],
        media: [['document_id' => (string) Str::uuid(), 'alt_en' => 'Icon', 'alt_ar' => 'أيقونة', 'sort_order' => 1]],
    ));

    $publishAction->execute($service->id);

    $quoteBefore = $catalogContract->currentQuote($service->id);
    expect($quoteBefore->available)->toBeTrue()
        ->and($quoteBefore->priceMinor)->toBe(20_000_00);

    $deactivateAction->execute($service->id, actorId: (string) Str::uuid());

    $quoteAfter = $catalogContract->currentQuote($service->id);
    expect($quoteAfter->available)->toBeFalse();
});

it('lists only published services sorted by sort_order and reorders them', function (): void {
    $createAction = app(CreateService::class);
    $publishAction = app(PublishService::class);
    $listQuery = app(ListPublishedServices::class);
    $reorderAction = app(ReorderServices::class);

    $s1 = $createAction->execute(new CreateServiceData(
        slug: 'srv-1-'.Str::random(6),
        nameEn: 'Service 1',
        nameAr: 'خدمة 1',
        shortDescriptionEn: 'Desc 1',
        shortDescriptionAr: 'وصف 1',
        detailedDescriptionEn: 'Full 1',
        detailedDescriptionAr: 'كامل 1',
        expectedDurationEn: '1 day',
        expectedDurationAr: 'يوم',
        priceMinor: 10_000_00,
        requirements: [['text_en' => 'Req 1', 'text_ar' => 'متطلب 1', 'sort_order' => 1]],
        media: [['document_id' => (string) Str::uuid(), 'alt_en' => 'Media 1', 'alt_ar' => 'وسائط 1', 'sort_order' => 1]],
    ));
    $publishAction->execute($s1->id);

    $s2 = $createAction->execute(new CreateServiceData(
        slug: 'srv-2-'.Str::random(6),
        nameEn: 'Service 2',
        nameAr: 'خدمة 2',
        shortDescriptionEn: 'Desc 2',
        shortDescriptionAr: 'وصف 2',
        detailedDescriptionEn: 'Full 2',
        detailedDescriptionAr: 'كامل 2',
        expectedDurationEn: '2 days',
        expectedDurationAr: 'يومان',
        priceMinor: 15_000_00,
        requirements: [['text_en' => 'Req 2', 'text_ar' => 'متطلب 2', 'sort_order' => 1]],
        media: [['document_id' => (string) Str::uuid(), 'alt_en' => 'Media 2', 'alt_ar' => 'وسائط 2', 'sort_order' => 1]],
    ));
    // Keep $s2 as draft

    $publishedList = $listQuery->execute();
    $slugs = array_map(fn ($s) => $s->slug, $publishedList);

    expect($slugs)->toContain($s1->slug)
        ->and($slugs)->not->toContain($s2->slug);

    // Reorder
    $reorderAction->execute([$s1->id => 5]);
    $updatedList = $listQuery->execute();
    $target = collect($updatedList)->firstWhere('id', $s1->id);
    expect($target->sortOrder)->toBe(5);
});
