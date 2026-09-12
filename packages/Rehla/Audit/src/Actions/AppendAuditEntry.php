<?php

declare(strict_types=1);

namespace Rehla\Audit\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Audit\Models\AuditEntry;

final class AppendAuditEntry implements AuditWriter
{
    private const array SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'authorization',
        'document_bytes',
        'file_contents',
        'card_number',
        'cvv',
    ];

    public function append(AppendAuditData $data): string
    {
        $id = (string) Str::uuid();
        $sanitizedMetadata = $this->sanitizeMetadata($data->metadata);

        AuditEntry::create([
            'id' => $id,
            'actor_type' => $data->actorType,
            'actor_id' => $data->actorId,
            'action' => $data->action,
            'subject_type' => $data->subjectType,
            'subject_id' => $data->subjectId,
            'metadata' => $sanitizedMetadata,
            'ip_hash' => $data->ipHash,
            'user_agent_hash' => $data->userAgentHash,
            'occurred_at' => CarbonImmutable::now(),
        ]);

        return $id;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeMetadata($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
