<?php

declare(strict_types=1);

namespace Rehla\Audit\Queries;

use DateTimeImmutable;
use Rehla\Audit\Data\AuditEntryData;
use Rehla\Audit\Models\AuditEntry;

final class ListAuditEntries
{
    /**
     * @return list<AuditEntryData>
     */
    public function execute(int $limit = 100): array
    {
        return AuditEntry::orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (AuditEntry $e): AuditEntryData => new AuditEntryData(
                id: (string) $e->id,
                actorType: (string) $e->actor_type,
                actorId: $e->actor_id ? (string) $e->actor_id : null,
                action: (string) $e->action,
                subjectType: (string) $e->subject_type,
                subjectId: $e->subject_id ? (string) $e->subject_id : null,
                metadata: (array) ($e->metadata ?? []),
                ipHash: $e->ip_hash ? (string) $e->ip_hash : null,
                userAgentHash: $e->user_agent_hash ? (string) $e->user_agent_hash : null,
                occurredAt: $e->occurred_at ? DateTimeImmutable::createFromInterface($e->occurred_at) : null,
            ))
            ->all();
    }
}
