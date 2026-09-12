<?php

declare(strict_types=1);

namespace Rehla\Notifications\Contracts;

use Rehla\Notifications\Data\DeliveryResult;
use Rehla\Notifications\Data\OutboxEnvelope;

interface NotificationChannel
{
    public function name(): string;

    public function send(OutboxEnvelope $envelope): DeliveryResult;
}
