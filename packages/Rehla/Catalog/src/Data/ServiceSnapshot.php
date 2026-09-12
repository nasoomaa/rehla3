<?php

declare(strict_types=1);

namespace Rehla\Catalog\Data;

final readonly class ServiceSnapshot
{
    /**
     * @param  array{en: string, ar: string}  $name
     * @param  array{short: array{en: string, ar: string}, detailed: array{en: string, ar: string}}  $descriptions
     * @param  array{en: string, ar: string}  $expectedDuration
     * @param  array{en: ?string, ar: ?string}  $notes
     * @param  array<int, array{text_en: string, text_ar: string, sort_order: int}>  $requirements
     */
    public function __construct(
        public string $serviceId,
        public array $name,
        public array $descriptions,
        public array $expectedDuration,
        public array $notes,
        public array $requirements,
        public int $priceMinor,
        public string $currency,
        public int $priceVersion,
    ) {}
}
