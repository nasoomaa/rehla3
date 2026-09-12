<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Catalog\Queries\ListAllServices;

final class ServiceController extends Controller
{
    public function index(ListAllServices $listAllServices): View
    {
        $services = $listAllServices->execute();

        return view('rehla-admin::services.index', [
            'services' => $services,
        ]);
    }

    public function store(Request $request, CreateService $createService): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'short_description_en' => ['required', 'string'],
            'short_description_ar' => ['required', 'string'],
            'detailed_description_en' => ['required', 'string'],
            'detailed_description_ar' => ['required', 'string'],
            'expected_duration_en' => ['required', 'string'],
            'expected_duration_ar' => ['required', 'string'],
            'price_minor' => ['required', 'integer', 'min:0'],
        ]);

        $slug = Str::slug($validated['name_en']).'-'.Str::random(6);
        $actorId = (string) Auth::guard('admin')->id();

        $createService->execute(new CreateServiceData(
            slug: $slug,
            nameEn: $validated['name_en'],
            nameAr: $validated['name_ar'],
            shortDescriptionEn: $validated['short_description_en'],
            shortDescriptionAr: $validated['short_description_ar'],
            detailedDescriptionEn: $validated['detailed_description_en'],
            detailedDescriptionAr: $validated['detailed_description_ar'],
            expectedDurationEn: $validated['expected_duration_en'],
            expectedDurationAr: $validated['expected_duration_ar'],
            priceMinor: (int) $validated['price_minor'],
            requirements: [
                ['text_en' => 'Passport copy', 'text_ar' => 'صورة الجواز', 'sort_order' => 1],
            ],
            media: [
                ['document_id' => (string) Str::uuid(), 'alt_en' => 'Banner', 'alt_ar' => 'بانر', 'sort_order' => 1],
            ],
            notesEn: null,
            notesAr: null,
        ), actorId: $actorId);

        return redirect('/admin/services')->with('success', 'Service created successfully.');
    }

    public function publish(string $id, PublishService $publishService): RedirectResponse
    {
        $actorId = (string) Auth::guard('admin')->id();
        $publishService->execute($id, actorId: $actorId);

        return redirect('/admin/services')->with('success', 'Service published successfully.');
    }
}
