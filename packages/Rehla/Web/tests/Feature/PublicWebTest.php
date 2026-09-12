<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Catalog\Data\ServiceData;

function publishedService(): ServiceData
{
    $slug = 'uae-visa-'.Str::random(6);
    $createAction = app(CreateService::class);
    $publishAction = app(PublishService::class);

    $created = $createAction->execute(new CreateServiceData(
        slug: $slug,
        nameEn: 'UAE Tourist Visa',
        nameAr: 'تأشيرة سياحة الإمارات',
        shortDescriptionEn: 'Fast 30-day visa',
        shortDescriptionAr: 'تأشيرة سريعة 30 يوم',
        detailedDescriptionEn: 'Complete 30-day entry visa with travel insurance included.',
        detailedDescriptionAr: 'تأشيرة دخول كاملة لمدة 30 يوم شاملة التأمين الطبي.',
        expectedDurationEn: '2-3 business days',
        expectedDurationAr: '٢-٣ أيام عمل',
        priceMinor: 50_000_00,
        requirements: [
            ['text_en' => 'Passport copy', 'text_ar' => 'صورة الجواز', 'sort_order' => 1],
        ],
        media: [
            ['document_id' => (string) Str::uuid(), 'alt_en' => 'Banner', 'alt_ar' => 'بانر', 'sort_order' => 1],
        ],
        notesEn: 'Non-refundable after processing starts',
        notesAr: 'غير قابلة للاسترداد بعد بدء المعالجة',
    ), actorId: (string) Str::uuid());

    return $publishAction->execute($created->id, actorId: (string) Str::uuid());
}

function formatSdg(int $minorUnits): string
{
    return number_format($minorUnits / 100, 2);
}

function orderCount(): int
{
    return DB::table('orders')->count();
}

function totalDebits(): int
{
    return (int) DB::table('ledger_entries')->where('type', 'debit')->count();
}

function executionCount(): int
{
    return DB::table('service_executions')->count();
}

it('shows service facts and keeps WhatsApp separate from ordering', function (): void {
    Config::set('rehla-integrations.whatsapp_number', '+249 91 234 5678');
    $service = publishedService();

    $response = test()->get("/services/{$service->slug}");
    $response->assertOk()
        ->assertSee($service->nameEn)
        ->assertSee(formatSdg($service->currentPriceMinor))
        ->assertSee('Order Now')
        ->assertSee('wa.me');

    expect(orderCount())->toBe(0)
        ->and(totalDebits())->toBe(0)
        ->and(executionCount())->toBe(0);
});

it('renders public catalog browse without auth', function (): void {
    $service = publishedService();

    $response = test()->get('/services');
    $response->assertOk()
        ->assertSee($service->nameEn)
        ->assertSee(formatSdg($service->currentPriceMinor));
});

it('renders home page with public catalog and content blocks', function (): void {
    $service = publishedService();

    $response = test()->get('/');
    $response->assertOk()
        ->assertSee($service->nameEn);
});

it('switches locale and persists to session', function (): void {
    $response = test()->get('/locale/ar');
    $response->assertRedirect();
    test()->followRedirects($response)->assertSessionHas('locale', 'ar');
});

it('returns 404 for nonexistent service slug', function (): void {
    test()->get('/services/nonexistent-service-slug-12345')
        ->assertNotFound();
});
