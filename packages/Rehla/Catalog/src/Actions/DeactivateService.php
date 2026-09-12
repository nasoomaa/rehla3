<?php

declare(strict_types=1);

namespace Rehla\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Catalog\Data\ServiceData;
use Rehla\Catalog\Enums\ServiceStatus;
use Rehla\Catalog\Models\Service;

final class DeactivateService
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

            $service->status = ServiceStatus::Deactivated;
            $service->save();

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'service.deactivated',
                    subjectType: 'service',
                    subjectId: $serviceId,
                    metadata: [
                        'slug' => (string) $service->slug,
                    ],
                ));
            }

            return $service->toData();
        });
    }
}
