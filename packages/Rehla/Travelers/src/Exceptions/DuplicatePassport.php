<?php

declare(strict_types=1);

namespace Rehla\Travelers\Exceptions;

use Rehla\Core\Errors\ErrorCode;
use RuntimeException;

final class DuplicatePassport extends RuntimeException
{
    public function __construct(string $message = 'Duplicate passport number', int $code = 409)
    {
        parent::__construct(ErrorCode::DUPLICATE_PASSPORT->value, $code);
    }
}
