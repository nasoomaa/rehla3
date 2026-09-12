<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Rehla\Fulfillment\Data\AddInternalNoteData;
use Rehla\Fulfillment\Exceptions\ExecutionAccessDeniedException;
use Rehla\Fulfillment\Models\ExecutionInternalNote;
use Rehla\Fulfillment\Models\ServiceExecution;
use Rehla\Identity\Enums\AbilityName;

final class AddInternalNote
{
    public function execute(AddInternalNoteData $data): void
    {
        if ($data->actor->type === 'staff' && ! in_array(AbilityName::ExecutionsManage->value, $data->actor->abilities, true)) {
            throw ExecutionAccessDeniedException::manageDenied();
        }

        $execution = ServiceExecution::findOrFail($data->executionId);

        ExecutionInternalNote::create([
            'id' => (string) Str::uuid(),
            'execution_id' => $execution->id,
            'staff_id' => $data->actor->id,
            'body' => $data->body,
            'created_at' => CarbonImmutable::now(),
        ]);
    }
}
