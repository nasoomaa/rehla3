<?php

declare(strict_types=1);

namespace Rehla\Core\Money;

use InvalidArgumentException;

final readonly class Money
{
    private function __construct(
        private int $minor,
        private string $currency,
    ) {}

    public static function sdg(int $minor): self
    {
        if ($minor < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }

        return new self($minor, 'SDG');
    }

    public function minor(): int
    {
        return $this->minor;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minor + $other->minor, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        $result = $this->minor - $other->minor;

        if ($result < 0) {
            throw new InvalidArgumentException('Subtraction would result in negative money.');
        }

        return new self($result, $this->currency);
    }

    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minor < $other->minor;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->minor === $other->minor;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: cannot operate on {$this->currency} and {$other->currency}."
            );
        }
    }
}
