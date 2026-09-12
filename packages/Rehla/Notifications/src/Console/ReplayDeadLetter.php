<?php

declare(strict_types=1);

namespace Rehla\Notifications\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Identity\Contracts\AuthorizesActor;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Notifications\Models\OutboxMessage;

final class ReplayDeadLetter extends Command
{
    protected $signature = 'rehla:outbox-replay {id} {--reason=} {--actor=}';

    protected $description = 'Replay a dead-lettered outbox message';

    public function handle(): int
    {
        $reason = trim((string) $this->option('reason'));
        if ($reason === '') {
            $this->error('Replay reason is required.');

            return 1;
        }

        $id = (string) $this->argument('id');
        $message = OutboxMessage::find($id);
        if ($message === null) {
            $this->error("Outbox message {$id} not found.");

            return 1;
        }

        $actorId = $this->option('actor');
        if ($actorId !== null && is_string($actorId) && trim($actorId) !== '') {
            $authorizer = app()->bound(AuthorizesActor::class) ? app(AuthorizesActor::class) : null;
            if ($authorizer !== null) {
                $actor = new ActorData(
                    id: trim($actorId),
                    type: 'staff',
                );
                if (! $authorizer->allows($actor, AbilityName::NotificationsManage)) {
                    $this->error('Forbidden: Actor lacks notifications.manage ability.');

                    return 1;
                }
            }
        }

        $message->update([
            'locked_at' => null,
            'locked_by' => null,
            'delivered_at' => null,
            'dead_lettered_at' => null,
            'attempts' => 0,
            'available_at' => CarbonImmutable::now(),
        ]);

        $auditWriter = app()->bound(AuditWriter::class) ? app(AuditWriter::class) : null;
        $auditWriter?->append(new AppendAuditData(
            actorType: $actorId !== null ? 'staff' : 'system',
            actorId: $actorId !== null ? (string) $actorId : null,
            action: 'outbox.replayed',
            subjectType: 'outbox_message',
            subjectId: $id,
            metadata: [
                'reason' => $reason,
                'event_name' => $message->event_name,
            ],
        ));

        $this->info("Outbox message {$id} queued for replay.");

        return 0;
    }
}
