<?php

declare(strict_types=1);

namespace Rehla\Catalog\Actions;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Catalog\Data\ServiceData;
use Rehla\Catalog\Exceptions\InvalidPriceException;
use Rehla\Catalog\Models\Service;
use Rehla\Catalog\Models\ServicePriceHistory;

final class ChangeServicePrice
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(string $serviceId, int $newPriceMinor, ?string $actorId = null, string $actorType = 'staff'): ServiceData
    {
        if ($newPriceMinor < 0) {
            throw InvalidPriceException::negativePrice($newPriceMinor);
        }

        return DB::transaction(function () use ($serviceId, $newPriceMinor, $actorId, $actorType): ServiceData {
            /** @var Service|null $service */
            $service = Service::lockForUpdate()->find($serviceId);
            if (! $service) {
                throw new \InvalidArgumentException("Service not found: {$serviceId}");
            }

            $oldPrice = (int) $service->current_price_minor;
            $oldVersion = (int) $service->price_version;
            $newVersion = $oldVersion + 1;
            $now = new DateTimeImmutable;

            $service->current_price_minor = $newPriceMinor;
            $service->price_version = $newVersion;
            $service->save();

            ServicePriceHistory::create([
                'id' => (string) Str::uuid(),
                'service_id' => $serviceId,
                'price_minor' => $newPriceMinor,
                'currency' => 'SDG',
                'version' => $newVersion,
                'changed_by' => $actorId,
                'effective_at' => $now,
                'created_at' => $now,
            ]);

            if ($this->auditWriter !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'service.price_changed',
                    subjectType: 'service',
                    subjectId: $serviceId,
                    metadata: [
                        'old_price_minor' => $oldPrice,
                        'new_price_minor' => $newPriceMinor,
                        'old_version' => $oldVersion,
                        'new_version' => $newVersion,
                        'currency' => 'SDG',
                    ],
                ));
            }

            return $service->toData();
        });
    }
}
