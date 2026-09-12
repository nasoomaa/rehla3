<?php

declare(strict_types=1);

namespace Rehla\Forms\Enums;

enum FieldType: string
{
    case ShortText = 'short_text';
    case LongText = 'long_text';
    case Email = 'email';
    case Phone = 'phone';
    case Number = 'number';
    case Date = 'date';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case File = 'file';
    case Image = 'image';
}
