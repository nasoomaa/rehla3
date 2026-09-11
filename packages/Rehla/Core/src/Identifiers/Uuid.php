<?php

declare(strict_types=1);

namespace Rehla\Core\Identifiers;

use InvalidArgumentException;
use Stringable;

final readonly class Uuid implements Stringable
{
    private const string PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    private function __construct(private string $value) {}

    /**
     * Generate a new random UUID v4.
     */
    public static function generate(): self
    {
        return new self(sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
        ));
    }

    /**
     * Create a UUID from an existing string, validating format.
     */
    public static function fromString(string $value): self
    {
        if (! preg_match(self::PATTERN, $value)) {
            throw new InvalidArgumentException("Invalid UUID format: {$value}");
        }

        return new self(strtolower($value));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
