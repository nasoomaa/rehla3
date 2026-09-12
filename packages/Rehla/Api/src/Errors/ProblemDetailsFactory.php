<?php

declare(strict_types=1);

namespace Rehla\Api\Errors;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ProblemDetailsFactory
{
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $extensions
     */
    public static function make(
        int $status,
        string $code,
        ?string $title = null,
        ?string $detail = null,
        ?string $type = null,
        array $errors = [],
        array $extensions = []
    ): JsonResponse {
        $typeSlug = strtolower(str_replace('_', '-', $code));
        $resolvedType = $type ?? "https://rehla.example/problems/{$typeSlug}";

        $titles = [
            'UNAUTHENTICATED' => app()->getLocale() === 'ar' ? 'غير مصرح به' : 'Unauthenticated',
            'UNAUTHORIZED' => app()->getLocale() === 'ar' ? 'وصول مرفوض' : 'Forbidden',
            'INVALID_CREDENTIALS' => app()->getLocale() === 'ar' ? 'بيانات الاعتماد غير صالحة' : 'Invalid credentials provided',
            'NOT_FOUND' => app()->getLocale() === 'ar' ? 'المورد غير موجود' : 'Resource not found',
            'VALIDATION_FAILED' => app()->getLocale() === 'ar' ? 'فشل التحقق من صحة البيانات' : 'The given data was invalid',
            'PRICE_CHANGED' => app()->getLocale() === 'ar' ? 'تغير سعر الخدمة' : 'Service price has changed',
            'FORM_VERSION_CHANGED' => app()->getLocale() === 'ar' ? 'تغير إصدار النموذج' : 'Form version has changed',
            'IDEMPOTENCY_CONFLICT' => app()->getLocale() === 'ar' ? 'تعارض في مفتاح التكرار' : 'Idempotency key reused with differing parameters',
            'INSUFFICIENT_FUNDS' => app()->getLocale() === 'ar' ? 'رصيد المحفظة غير كافٍ' : 'Insufficient wallet balance',
            'TOO_MANY_REQUESTS' => app()->getLocale() === 'ar' ? 'تجاوزت الحد المسموح من الطلبات' : 'Too many requests',
            'INTERNAL_ERROR' => app()->getLocale() === 'ar' ? 'حدث خطأ في الخادم' : 'An unexpected error occurred',
        ];

        $payload = array_merge([
            'type' => $resolvedType,
            'title' => $title ?? ($titles[$code] ?? $code),
            'status' => $status,
            'code' => $code,
            'trace_id' => (string) Str::uuid(),
        ], $extensions);

        if ($detail !== null) {
            $payload['detail'] = $detail;
        }

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status, [
            'Content-Type' => 'application/problem+json',
        ]);
    }
}
