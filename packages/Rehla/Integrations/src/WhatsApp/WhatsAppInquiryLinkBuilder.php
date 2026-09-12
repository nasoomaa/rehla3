<?php

declare(strict_types=1);

namespace Rehla\Integrations\WhatsApp;

use Rehla\Integrations\Contracts\InquiryLinkBuilder;
use Rehla\Integrations\Data\InquiryLink;

final class WhatsAppInquiryLinkBuilder implements InquiryLinkBuilder
{
    public function forService(string $serviceName, string $locale = 'ar'): InquiryLink
    {
        $rawNumber = (string) config('rehla-integrations.whatsapp_number', env('REHLA_WHATSAPP_NUMBER', '249912345678'));
        $sanitizedNumber = (string) preg_replace('/\D+/', '', $rawNumber);

        $text = match (strtolower(trim($locale))) {
            'ar' => "مرحبًا رحلة، أود الاستفسار عن خدمة {$serviceName}.",
            default => "Hello Rehla, I would like to inquire about {$serviceName}.",
        };

        $encodedText = rawurlencode($text);
        $url = "https://wa.me/{$sanitizedNumber}?text={$encodedText}";

        return new InquiryLink(
            url: $url,
            text: $text,
            locale: $locale,
        );
    }
}
