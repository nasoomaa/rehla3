<?php

declare(strict_types=1);

namespace Rehla\Integrations\Contracts;

use Rehla\Integrations\Data\InquiryLink;

interface InquiryLinkBuilder
{
    public function forService(string $serviceName, string $locale = 'ar'): InquiryLink;
}
