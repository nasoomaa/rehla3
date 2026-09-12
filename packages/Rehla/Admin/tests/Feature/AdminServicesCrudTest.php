<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Rehla\Catalog\Queries\ListAllServices;
use Tests\Support\StaffTestHelper;

it('performs full CRUD lifecycle on services', function (): void {
    $staff = StaffTestHelper::createStaff(abilities: ['services.manage']);
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    // 1. Create service draft
    $this->actingAs($user, 'admin')->post('/admin/services', [
        'name_en' => 'Egypt Tourist Visa',
        'name_ar' => 'تأشيرة سياحة مصر',
        'short_description_en' => 'Fast Egypt tourist visa processing',
        'short_description_ar' => 'استخراج تأشيرة سياحة مصر بسرعة',
        'detailed_description_en' => 'Detailed description for Egypt visa',
        'detailed_description_ar' => 'الوصف الكامل لتأشيرة مصر',
        'expected_duration_en' => '3 business days',
        'expected_duration_ar' => '3 أيام عمل',
        'price_minor' => 12000000,
    ])->assertRedirect('/admin/services');

    $services = app(ListAllServices::class)->execute();
    $service = collect($services)->firstWhere('nameEn', 'Egypt Tourist Visa');
    expect($service)->not->toBeNull();

    // 2. Update service content
    $this->actingAs($user, 'admin')->put("/admin/services/{$service->id}/content", [
        'name_en' => 'Egypt Express Visa',
        'name_ar' => 'تأشيرة مصر السريعة',
        'short_description_en' => 'Updated short description',
        'short_description_ar' => 'وصف قصير محدث',
        'detailed_description_en' => 'Updated detailed description',
        'detailed_description_ar' => 'وصف تفصيلي محدث',
        'expected_duration_en' => '1 business day',
        'expected_duration_ar' => 'يوم عمل واحد',
    ])->assertRedirect('/admin/services');

    $updatedServices = app(ListAllServices::class)->execute();
    $updatedService = collect($updatedServices)->firstWhere('id', $service->id);
    expect($updatedService->nameEn)->toBe('Egypt Express Visa')
        ->and($updatedService->expectedDurationEn)->toBe('1 business day');

    // 3. Change price
    $this->actingAs($user, 'admin')->post("/admin/services/{$service->id}/price", [
        'new_price_minor' => 15000000,
    ])->assertRedirect('/admin/services');

    $priceUpdated = collect(app(ListAllServices::class)->execute())->firstWhere('id', $service->id);
    expect($priceUpdated->currentPriceMinor)->toBe(15000000);

    // 4. Seed requirements + media directly so PublishService validation passes
    DB::table('services')->where('id', $service->id)->update([
        'requirements' => json_encode([['label_en' => 'Valid Passport', 'label_ar' => 'جواز سفر ساري']]),
        'media' => json_encode([['type' => 'image', 'url' => 'https://example.com/img.jpg']]),
    ]);

    $this->actingAs($user, 'admin')->post("/admin/services/{$service->id}/publish")
        ->assertRedirect('/admin/services');

    $published = collect(app(ListAllServices::class)->execute())->firstWhere('id', $service->id);
    expect($published)->not->toBeNull()
        ->and($published->status->value)->toBe('published');

    // 5. Deactivate the now-published service
    $this->actingAs($user, 'admin')->post("/admin/services/{$service->id}/deactivate")
        ->assertRedirect('/admin/services');

    $deactivated = collect(app(ListAllServices::class)->execute())->firstWhere('id', $service->id);
    expect($deactivated)->not->toBeNull()
        ->and($deactivated->status->value)->toBe('deactivated');
});
