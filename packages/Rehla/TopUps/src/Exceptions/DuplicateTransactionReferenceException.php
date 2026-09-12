<?php

declare(strict_types=1);

namespace Rehla\TopUps\Exceptions;

use DomainException;

final class DuplicateTransactionReferenceException extends DomainException
{
    public static function forReference(string $reference): self
    {
        return new self("The bank transfer reference '{$reference}' has already been submitted and cannot be reused.");
    }
}
