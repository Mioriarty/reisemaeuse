<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    /**
     * netcup only invokes schedule:run once an hour, on a minute we do not
     * control. schedule:run fires a task only when the current minute matches
     * its expression, so anything narrower than `* * * * *` may never run at
     * all - that is how prune-comment-ip-hashes silently died on `30 3 * * *`.
     */
    public function test_every_scheduled_task_survives_an_hourly_cron(): void
    {
        $events = app(Schedule::class)->events();

        $this->assertNotEmpty($events, 'The scheduler defines no tasks at all.');

        foreach ($events as $event) {
            $this->assertSame(
                '* * * * *',
                $event->expression,
                'Task "'.($event->description ?: $event->getSummaryForDisplay()).'" uses '
                .'"'.$event->expression.'". The cron minute is not ours to pick, so the '
                .'cadence has to come from the cron and the task has to be idempotent.',
            );
        }
    }
}
