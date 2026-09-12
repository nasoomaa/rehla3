<?php

declare(strict_types=1);

namespace Rehla\Forms\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Data\PublishedFormData;
use Rehla\Forms\Enums\FormVersionStatus;

final class FormVersion extends Model
{
    use HasUuids;

    protected $table = 'form_versions';

    protected $fillable = [
        'id',
        'service_id',
        'version',
        'schema',
        'checksum',
        'status',
        'published_by',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'schema' => 'array',
        'status' => FormVersionStatus::class,
        'published_at' => 'immutable_datetime',
    ];

    public function toPublishedData(): PublishedFormData
    {
        $fields = array_map(
            fn (array $item): FormFieldData => FormFieldData::fromArray($item),
            (array) ($this->schema ?? [])
        );

        return new PublishedFormData(
            id: (string) $this->id,
            serviceId: (string) $this->service_id,
            version: (int) $this->version,
            status: $this->status instanceof FormVersionStatus ? $this->status : FormVersionStatus::from((string) $this->status),
            checksum: $this->checksum ? (string) $this->checksum : null,
            fields: $fields,
            publishedAt: $this->published_at ? DateTimeImmutable::createFromInterface($this->published_at) : null,
            publishedBy: $this->published_by ? (string) $this->published_by : null,
        );
    }
}
