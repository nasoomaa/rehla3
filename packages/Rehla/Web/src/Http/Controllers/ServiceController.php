<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Controllers;

use Illuminate\Contracts\View\View;
use Rehla\Catalog\Enums\ServiceStatus;
use Rehla\Catalog\Queries\GetServiceDetails;
use Rehla\Catalog\Queries\ListPublishedServices;
use Rehla\Integrations\Contracts\InquiryLinkBuilder;

final class ServiceController
{
    public function index(ListPublishedServices $listServices): View
    {
        $services = $listServices->execute();

        return view('rehla-web::public.services.index', [
            'services' => $services,
        ]);
    }

    public function show(string $slug, GetServiceDetails $getDetails, InquiryLinkBuilder $inquiryBuilder): View
    {
        $service = $getDetails->executeBySlug($slug);

        if ($service === null || $service->status !== ServiceStatus::Published) {
            abort(404);
        }

        $serviceNameForInquiry = app()->getLocale() === 'ar' ? $service->nameAr : $service->nameEn;
        $inquiryLink = $inquiryBuilder->forService($serviceNameForInquiry, app()->getLocale());

        return view('rehla-web::public.services.show', [
            'service' => $service,
            'inquiryLink' => $inquiryLink,
        ]);
    }
}
