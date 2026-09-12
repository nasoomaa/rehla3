<?php

declare(strict_types=1);

namespace Rehla\Forms\Actions;

use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Forms\Data\PublishedFormData;
use Rehla\Forms\Enums\FormVersionStatus;
use Rehla\Forms\Models\FormVersion;

final class PublishFormVersion
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(string $draftId, ?string $actorId = null, string $actorType = 'staff'): PublishedFormData
    {
        return DB::transaction(function () use ($draftId, $actorId, $actorType): PublishedFormData {
            /** @var FormVersion|null $draft */
            $draft = FormVersion::lockForUpdate()->find($draftId);

            if (! $draft) {
                throw new DomainException("Form draft not found: {$draftId}");
            }

            if ($draft->status !== FormVersionStatus::Draft) {
                throw new DomainException("Form is already published or cannot be republished: {$draftId}");
            }

            $serviceId = (string) $draft->service_id;

            // Compute next version
            $latestVersion = (int) FormVersion::where('service_id', $serviceId)
                ->where('status', FormVersionStatus::Published)
                ->max('version');

            $nextVersion = $latestVersion + 1;

            // Deterministic JSON checksum
            $schemaArray = (array) $draft->schema;
            ksort($schemaArray);
            $normalizedJson = json_encode($schemaArray, JSON_THROW_ON_ERROR);
            $checksum = hash('sha256', $normalizedJson);

            $now = new DateTimeImmutable;
            $draft->version = $nextVersion;
            $draft->status = FormVersionStatus::Published;
            $draft->checksum = $checksum;
            $draft->published_at = $now;
            $draft->published_by = $actorId;
            $draft->save();

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'form.published',
                    subjectType: 'form_version',
                    subjectId: (string) $draft->id,
                    metadata: [
                        'service_id' => $serviceId,
                        'version' => $nextVersion,
                        'checksum' => $checksum,
                    ],
                ));
            }

            return $draft->toPublishedData();
        });
    }
}
