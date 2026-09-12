<?php

declare(strict_types=1);

namespace Rehla\Forms\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Data\PublishedFormData;
use Rehla\Forms\Enums\FormVersionStatus;
use Rehla\Forms\Models\FormVersion;

final class UpdateFormDraft
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    /**
     * @param  array<int, FormFieldData>  $fields
     */
    public function execute(string $draftId, array $fields, ?string $actorId = null, string $actorType = 'staff'): PublishedFormData
    {
        return DB::transaction(function () use ($draftId, $fields, $actorId, $actorType): PublishedFormData {
            /** @var FormVersion|null $draft */
            $draft = FormVersion::lockForUpdate()->find($draftId);

            if (! $draft) {
                throw new DomainException("Form draft not found: {$draftId}");
            }

            if ($draft->status !== FormVersionStatus::Draft) {
                throw new DomainException("Cannot edit a published or archived form version: {$draftId}");
            }

            $schema = array_map(fn (FormFieldData $f): array => $f->toArray(), $fields);
            $draft->schema = $schema;
            $draft->save();

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'form.draft_updated',
                    subjectType: 'form_version',
                    subjectId: $draftId,
                    metadata: ['service_id' => (string) $draft->service_id],
                ));
            }

            return $draft->toPublishedData();
        });
    }
}
