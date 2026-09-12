<?php

declare(strict_types=1);

namespace Rehla\Forms\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Data\PublishedFormData;
use Rehla\Forms\Enums\FormVersionStatus;
use Rehla\Forms\Models\FormVersion;

final class CreateFormDraft
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    /**
     * @param  array<int, FormFieldData>  $fields
     */
    public function execute(string $serviceId, array $fields, ?string $actorId = null, string $actorType = 'staff'): PublishedFormData
    {
        return DB::transaction(function () use ($serviceId, $fields, $actorId, $actorType): PublishedFormData {
            $schema = array_map(fn (FormFieldData $f): array => $f->toArray(), $fields);

            /** @var FormVersion $draft */
            $draft = FormVersion::create([
                'id' => (string) Str::uuid(),
                'service_id' => $serviceId,
                'version' => 0,
                'schema' => $schema,
                'status' => FormVersionStatus::Draft,
                'created_by' => $actorId,
            ]);

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'form.draft_created',
                    subjectType: 'form_version',
                    subjectId: (string) $draft->id,
                    metadata: ['service_id' => $serviceId],
                ));
            }

            return $draft->toPublishedData();
        });
    }
}
