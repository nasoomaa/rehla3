<?php

declare(strict_types=1);

namespace Rehla\Catalog\Data;

use DateTimeImmutable;
use Rehla\Catalog\Enums\ServiceStatus;

final readonly class ServiceData
{
    /**
     * @param  array<int, array{text_en: string, text_ar: string, sort_order: int}>  $requirements
     * @param  array<int, array{document_id: string, alt_en: string, alt_ar: string, sort_order: int}>  $media
     */
    public function __construct(
        public string $id,
        public string $slug,
        public string $nameEn,
        public string $nameAr,
        public string $shortDescriptionEn,
        public string $shortDescriptionAr,
        public string $detailedDescriptionEn,
        public string $detailedDescriptionAr,
        public string $expectedDurationEn,
        public string $expectedDurationAr,
        public ?string $notesEn,
        public ?string $notesAr,
        public int $currentPriceMinor,
        public string $currency,
        public int $priceVersion,
        public ServiceStatus $status,
        public int $sortOrder,
        public array $requirements,
        public array $media,
        public ?DateTimeImmutable $publishedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}
}
