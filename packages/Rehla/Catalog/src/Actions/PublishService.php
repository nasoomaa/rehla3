<?php

declare(strict_types=1);

namespace Rehla\Catalog\Actions;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Catalog\Data\ServiceData;
use Rehla\Catalog\Enums\ServiceStatus;
use Rehla\Catalog\Exceptions\ServicePublishingValidationException;
use Rehla\Catalog\Models\Service;

final class PublishService
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(string $serviceId, ?string $actorId = null, string $actorType = 'staff'): ServiceData
    {
        return DB::transaction(function () use ($serviceId, $actorId, $actorType): ServiceData {
            /** @var Service|null $service */
            $service = Service::lockForUpdate()->find($serviceId);
            if (! $service) {
                throw new \InvalidArgumentException("Service not found: {$serviceId}");
            }

            // Validate readiness
            if (
                trim((string) $service->name_en) === '' ||
                trim((string) $service->name_ar) === '' ||
                trim((string) $service->short_description_en) === '' ||
                trim((string) $service->short_description_ar) === '' ||
                trim((string) $service->detailed_description_en) === '' ||
                trim((string) $service->detailed_description_ar) === '' ||
                trim((string) $service->expected_duration_en) === '' ||
                trim((string) $service->expected_duration_ar) === '' ||
                empty($service->requirements) ||
                empty($service->media)
            ) {
                throw ServicePublishingValidationException::missingRequirements($serviceId);
            }

            $now = new DateTimeImmutable;
            $service->status = ServiceStatus::Published;
            $service->published_at = $now;
            $service->save();

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'service.published',
                    subjectType: 'service',
                    subjectId: $serviceId,
                    metadata: [
                        'published_at' => $now->format(DATE_ATOM),
                    ],
                ));
            }

            return $service->toData();
        });
    }
}
