<?php

use Illuminate\Console\Scheduling\Schedule;

test('failed queue jobs worden na een week opgeschoond', function () {
    $events = collect(app(Schedule::class)->events());

    expect($events->contains(function ($event): bool {
        return str_contains($event->command ?? '', 'queue:prune-failed --hours=168')
            && $event->getExpression() === '0 3 * * *';
    }))->toBeTrue();
});
