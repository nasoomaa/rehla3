<?php

declare(strict_types=1);

namespace Rehla\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Catalog\Data\ServiceData;
use Rehla\Catalog\Models\Service;

final class UpdateServiceContent
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(string $serviceId, CreateServiceData $data, ?string $actorId = null, string $actorType = 'staff'): ServiceData
    {
        return DB::transaction(function () use ($serviceId, $data, $actorId, $actorType): ServiceData {
            /** @var Service|null $service */
            $service = Service::lockForUpdate()->find($serviceId);
            if (! $service) {
                throw new \InvalidArgumentException("Service not found: {$serviceId}");
            }

            $service->update([
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
                'requirements' => $data->requirements,
                'media' => $data->media,
                'sort_order' => $data->sortOrder,
            ]);

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'service.content_updated',
                    subjectType: 'service',
                    subjectId: $serviceId,
                    metadata: ['slug' => $data->slug],
                ));
            }

            return $service->toData();
        });
    }
}
