<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rehla\Api\Http\Resources\V1\ServiceResource;
use Rehla\Catalog\Queries\GetServiceDetails;
use Rehla\Catalog\Queries\ListPublishedServices;
use Rehla\Forms\Queries\GetPublishedForm;

final class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = app(ListPublishedServices::class)->execute();

        return response()->json([
            'data' => ServiceResource::collection($services)->resolve(),
            'links' => [
                'first' => url('/api/v1/services?page=1'),
                'last' => url('/api/v1/services?page=1'),
                'prev' => null,
                'next' => null,
            ],
            'meta' => [
                'total' => count($services),
                'per_page' => 50,
                'current_page' => 1,
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $service = app(GetServiceDetails::class)->executeBySlug($slug);
        if ($service === null) {
            $service = app(GetServiceDetails::class)->execute($slug);
        }
        abort_unless($service !== null, 404);

        return response()->json([
            'data' => (new ServiceResource($service))->resolve(),
        ]);
    }

    public function applicationForm(string $slug): JsonResponse
    {
        $service = app(GetServiceDetails::class)->executeBySlug($slug);
        if ($service === null) {
            $service = app(GetServiceDetails::class)->execute($slug);
        }
        abort_unless($service !== null, 404);

        $form = app(GetPublishedForm::class)->handle($service->id);
        abort_unless($form !== null, 404);

        return response()->json([
            'data' => [
                'form_version_id' => $form->id,
                'version' => $form->version,
                'fields' => array_map(fn ($f): array => (array) $f, $form->fields),
            ],
        ]);
    }
}
