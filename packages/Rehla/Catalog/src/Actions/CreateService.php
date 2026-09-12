<?php

declare(strict_types=1);

namespace Rehla\Catalog\Actions;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Catalog\Data\ServiceData;
use Rehla\Catalog\Enums\ServiceStatus;
use Rehla\Catalog\Exceptions\InvalidPriceException;
use Rehla\Catalog\Models\Service;
use Rehla\Catalog\Models\ServicePriceHistory;

final class CreateService
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(CreateServiceData $data, ?string $actorId = null, string $actorType = 'staff'): ServiceData
    {
        if ($data->priceMinor < 0) {
            throw InvalidPriceException::negativePrice($data->priceMinor);
        }

        return DB::transaction(function () use ($data, $actorId, $actorType): ServiceData {
            $serviceId = (string) Str::uuid();
            $now = new DateTimeImmutable;

            $service = Service::create([
                'id' => $serviceId,
                'slug' => $data->slug,
                'name_en' => $data->nameEn,
                'name_ar' => $data->nameAr,
                'short_description_en' => $data->shortDescriptionEn,
                'short_description_ar' => $data->shortDescriptionAr,
                'detailed_description_en' => $data->detailedDescriptionEn,
                'detailed_description_ar' => $data->detailedDescriptionAr,
                'expected_duration_en' => $data->expectedDurationEn,
                'expected_duration_ar' => $data->expectedDurationAr,
                'notes_en' => $data->notesEn,
                'notes_ar' => $data->notesAr,
                'current_price_minor' => $data->priceMinor,
                'currency' => 'SDG',
                'price_version' => 1,
                'status' => ServiceStatus::Draft,
                'sort_order' => $data->sortOrder,
                'requirements' => $data->requirements,
                'media' => $data->media,
            ]);

            ServicePriceHistory::create([
                'id' => (string) Str::uuid(),
                'service_id' => $serviceId,
                'price_minor' => $data->priceMinor,
                'currency' => 'SDG',
                'version' => 1,
                'changed_by' => $actorId,
                'effective_at' => $now,
                'created_at' => $now,
            ]);

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'service.created',
                    subjectType: 'service',
                    subjectId: $serviceId,
                    metadata: [
                        'slug' => $data->slug,
                        'price_minor' => $data->priceMinor,
                        'currency' => 'SDG',
                    ],
                ));
            }

            return $service->toData();
        });
    }
}
