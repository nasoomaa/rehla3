<?php

declare(strict_types=1);

namespace Rehla\Forms\Data;

use Rehla\Forms\Enums\FieldType;

final readonly class FormFieldData
{
    /**
     * @param  array<int, array{value: string, label_en: string, label_ar: string}>  $options
     * @param  array<string, mixed>  $validation
     */
    public function __construct(
        public string $key,
        public FieldType $type,
        public string $labelEn,
        public string $labelAr,
        public int $order = 0,
        public bool $required = true,
        public ?string $helperEn = null,
        public ?string $helperAr = null,
        public array $options = [],
        public array $validation = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type->value,
            'label' => [
                'en' => $this->labelEn,
                'ar' => $this->labelAr,
            ],
            'order' => $this->order,
            'required' => $this->required,
            'helper' => [
                'en' => $this->helperEn,
                'ar' => $this->helperAr,
            ],
            'options' => $this->options,
            'validation' => $this->validation,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $type = FieldType::from((string) ($data['type'] ?? 'short_text'));

        return new self(
            key: (string) ($data['key'] ?? ''),
            type: $type,
            labelEn: (string) ($data['label']['en'] ?? $data['label_en'] ?? ''),
            labelAr: (string) ($data['label']['ar'] ?? $data['label_ar'] ?? ''),
            order: (int) ($data['order'] ?? 0),
            required: (bool) ($data['required'] ?? true),
            helperEn: isset($data['helper']['en']) ? (string) $data['helper']['en'] : ($data['helper_en'] ?? null),
            helperAr: isset($data['helper']['ar']) ? (string) $data['helper']['ar'] : ($data['helper_ar'] ?? null),
            options: (array) ($data['options'] ?? []),
            validation: (array) ($data['validation'] ?? []),
        );
    }
}
