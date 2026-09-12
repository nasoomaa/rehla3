<?php

declare(strict_types=1);

namespace Rehla\Forms\Exceptions;

use DomainException;

final class FormValidationFailedException extends DomainException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(
        string $message,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }
}
