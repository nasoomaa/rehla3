<?php

declare(strict_types=1);

namespace Rehla\Catalog\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rehla\Catalog\Data\ServiceData;
use Rehla\Catalog\Data\ServiceSnapshot;
use Rehla\Catalog\Enums\ServiceStatus;

final class Service extends Model
{
    use HasUuids;

    protected $table = 'services';

    protected $fillable = [
        'id',
        'slug',
        'name_en',
        'name_ar',
        'short_description_en',
        'short_description_ar',
        'detailed_description_en',
        'detailed_description_ar',
        'expected_duration_en',
        'expected_duration_ar',
        'notes_en',
        'notes_ar',
        'current_price_minor',
        'currency',
        'price_version',
        'status',
        'sort_order',
        'requirements',
        'media',
        'published_at',
    ];

    protected $casts = [
        'current_price_minor' => 'integer',
        'price_version' => 'integer',
        'sort_order' => 'integer',
        'status' => ServiceStatus::class,
        'requirements' => 'array',
        'media' => 'array',
        'published_at' => 'immutable_datetime',
    ];

    /**
     * @return HasMany<ServicePriceHistory, $this>
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(ServicePriceHistory::class, 'service_id')->orderBy('version', 'asc');
    }

    public function toData(): ServiceData
    {
        return new ServiceData(
            id: (string) $this->id,
            slug: (string) $this->slug,
            nameEn: (string) $this->name_en,
            nameAr: (string) $this->name_ar,
            shortDescriptionEn: (string) $this->short_description_en,
            shortDescriptionAr: (string) $this->short_description_ar,
            detailedDescriptionEn: (string) $this->detailed_description_en,
            detailedDescriptionAr: (string) $this->detailed_description_ar,
            expectedDurationEn: (string) $this->expected_duration_en,
            expectedDurationAr: (string) $this->expected_duration_ar,
            notesEn: $this->notes_en ? (string) $this->notes_en : null,
            notesAr: $this->notes_ar ? (string) $this->notes_ar : null,
            currentPriceMinor: (int) $this->current_price_minor,
            currency: (string) $this->currency,
            priceVersion: (int) $this->price_version,
            status: $this->status instanceof ServiceStatus ? $this->status : ServiceStatus::from((string) $this->status),
            sortOrder: (int) $this->sort_order,
            requirements: (array) ($this->requirements ?? []),
            media: (array) ($this->media ?? []),
            publishedAt: $this->published_at ? DateTimeImmutable::createFromInterface($this->published_at) : null,
            createdAt: DateTimeImmutable::createFromInterface($this->created_at),
            updatedAt: DateTimeImmutable::createFromInterface($this->updated_at),
        );
    }

    public function toSnapshot(): ServiceSnapshot
    {
        return new ServiceSnapshot(
            serviceId: (string) $this->id,
            name: [
                'en' => (string) $this->name_en,
                'ar' => (string) $this->name_ar,
            ],
            descriptions: [
                'short' => [
                    'en' => (string) $this->short_description_en,
                    'ar' => (string) $this->short_description_ar,
                ],
                'detailed' => [
                    'en' => (string) $this->detailed_description_en,
                    'ar' => (string) $this->detailed_description_ar,
                ],
            ],
            expectedDuration: [
                'en' => (string) $this->expected_duration_en,
                'ar' => (string) $this->expected_duration_ar,
            ],
            notes: [
                'en' => $this->notes_en ? (string) $this->notes_en : null,
                'ar' => $this->notes_ar ? (string) $this->notes_ar : null,
            ],
            requirements: (array) ($this->requirements ?? []),
            priceMinor: (int) $this->current_price_minor,
            currency: (string) $this->currency,
            priceVersion: (int) $this->price_version,
        );
    }
}
