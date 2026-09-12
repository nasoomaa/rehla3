<?php

declare(strict_types=1);

namespace Rehla\Notifications\Data;

final readonly class DeliveryResult
{
    public function __construct(
        public bool $successful,
        public string $channel,
        public ?string $error = null,
    ) {}

    public static function success(string $channel): self
    {
        return new self(
            successful: true,
            channel: $channel,
        );
    }

    public static function failure(string $channel, string $error): self
    {
        return new self(
            successful: false,
            channel: $channel,
            error: $error,
        );
    }
}
