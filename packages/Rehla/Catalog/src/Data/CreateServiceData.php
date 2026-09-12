<?php

declare(strict_types=1);

namespace Rehla\Catalog\Data;

final readonly class CreateServiceData
{
    /**
     * @param  array<int, array{text_en: string, text_ar: string, sort_order: int}>  $requirements
     * @param  array<int, array{document_id: string, alt_en: string, alt_ar: string, sort_order: int}>  $media
     */
    public function __construct(
        public string $slug,
        public string $nameEn,
        public string $nameAr,
        public string $shortDescriptionEn,
        public string $shortDescriptionAr,
        public string $detailedDescriptionEn,
        public string $detailedDescriptionAr,
        public string $expectedDurationEn,
        public string $expectedDurationAr,
        public int $priceMinor,
        public ?string $notesEn = null,
        public ?string $notesAr = null,
        public array $requirements = [],
        public array $media = [],
        public int $sortOrder = 0,
    ) {}
}
