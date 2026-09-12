<?php

declare(strict_types=1);

namespace Rehla\Forms\Enums;

enum FormVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
