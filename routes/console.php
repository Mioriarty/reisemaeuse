<?php

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\Schedule;

/*
 * Everything recurring runs from a single netcup scheduled task:
 *
 *     * * * * *  cd ~/httpdocs && php artisan schedule:run
 *
 * Shared hosting has no daemons, so there is no queue worker running in the
 * background - the scheduler drains the queue once a minute instead.
 */

// Newsletter sending and anything else queued. --stop-when-empty keeps the
// process short-lived; --max-time stops it overlapping the next tick.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// A deploy self-heals within a minute even when netcup's Git deploy actions
// silently do not run, which they sometimes do not.
Schedule::command('wandermaeuse:post-deploy --if-changed')
    ->everyMinute()
    ->withoutOverlapping();

// Scheduled entries go live on their own.
Schedule::call(function () {
    Post::where('status', Post::STATUS_SCHEDULED)
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->update(['status' => Post::STATUS_PUBLISHED]);
})->everyTenMinutes()->name('publish-scheduled-posts');

// The IP hash exists only to rate-limit comments; after a week it is useless
// to us and should not be lying around.
Schedule::call(function () {
    Comment::whereNotNull('ip_hash')
        ->where('created_at', '<', now()->subDays(7))
        ->update(['ip_hash' => null]);
})->dailyAt('03:30')->name('prune-comment-ip-hashes');

Schedule::command('queue:prune-failed --hours=336')->weekly();
