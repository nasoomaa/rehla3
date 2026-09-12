<?php

declare(strict_types=1);

namespace Rehla\Content\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Rehla\Content\Data\ContentBlockData;
use Rehla\Content\Data\LocalizedContentBlockView;
use Rehla\Content\Enums\ContentStatus;

final class ContentBlock extends Model
{
    use HasUuids;

    protected $table = 'content_blocks';

    protected $fillable = [
        'id',
        'key',
        'title_en',
        'title_ar',
        'body_en',
        'body_ar',
        'status',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => ContentStatus::class,
        'published_at' => 'immutable_datetime',
    ];

    public function toData(): ContentBlockData
    {
        return new ContentBlockData(
            id: (string) $this->id,
            key: (string) $this->key,
            titleEn: (string) $this->title_en,
            titleAr: (string) $this->title_ar,
            bodyEn: (string) $this->body_en,
            bodyAr: (string) $this->body_ar,
            status: $this->status instanceof ContentStatus ? $this->status : ContentStatus::from((string) $this->status),
            publishedAt: $this->published_at ? DateTimeImmutable::createFromInterface($this->published_at) : null,
            createdBy: $this->created_by ? (string) $this->created_by : null,
            updatedBy: $this->updated_by ? (string) $this->updated_by : null,
            createdAt: $this->created_at ? DateTimeImmutable::createFromInterface($this->created_at) : null,
            updatedAt: $this->updated_at ? DateTimeImmutable::createFromInterface($this->updated_at) : null,
        );
    }

    public function toLocalizedView(string $locale): LocalizedContentBlockView
    {
        $isAr = str_starts_with(strtolower(trim($locale)), 'ar');
        $direction = $isAr ? 'rtl' : 'ltr';

        if ($isAr) {
            $title = trim((string) $this->title_ar) !== '' ? (string) $this->title_ar : (string) $this->title_en;
            $body = trim((string) $this->body_ar) !== '' ? (string) $this->body_ar : (string) $this->body_en;
        } else {
            $title = trim((string) $this->title_en) !== '' ? (string) $this->title_en : (string) $this->title_ar;
            $body = trim((string) $this->body_en) !== '' ? (string) $this->body_en : (string) $this->body_ar;
        }

        return new LocalizedContentBlockView(
            key: (string) $this->key,
            locale: $isAr ? 'ar' : 'en',
            direction: $direction,
            title: $title,
            body: $body,
            publishedAt: $this->published_at ? DateTimeImmutable::createFromInterface($this->published_at) : null,
        );
    }
}
