<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

it('ensures high-value recurring jobs are configured with singleton lock protections', function (): void {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    expect($events)->not->toBeEmpty();

    // Verify scheduled commands prevent overlapping and run on one server
    $scheduledCommands = array_map(fn ($e): string => (string) $e->command, $events);

    // There should be scheduled tasks for outbox processing or reconciliation
    expect(count($events))->toBeGreaterThan(0);

    foreach ($events as $event) {
        expect($event->withoutOverlapping)->toBeTrue();
        expect($event->onOneServer)->toBeTrue();
    }
});
