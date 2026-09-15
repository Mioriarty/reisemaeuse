<?php

namespace App\Jobs;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignSend;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one slice of a campaign, then re-queues itself for the next slice.
 *
 * netcup's shared SMTP throttles hard, so we deliberately trickle: a chunk per
 * job, one job per scheduler tick. A few hundred subscribers go out over a few
 * minutes instead of tripping the rate limit and failing the whole run.
 */
class SendCampaignChunk implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** How many mails one pass through the queue hands to SMTP. */
    public const CHUNK = 20;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = Campaign::with('post')->find($this->campaignId);

        if (! $campaign || $campaign->status === 'sent') {
            return;
        }

        $pending = CampaignSend::with('subscriber')
            ->where('campaign_id', $campaign->id)
            ->whereNull('sent_at')
            ->limit(self::CHUNK)
            ->get();

        if ($pending->isEmpty()) {
            $campaign->update(['status' => 'sent', 'sent_at' => now()]);

            return;
        }

        foreach ($pending as $send) {
            $subscriber = $send->subscriber;

            // Someone may have unsubscribed between queueing and sending.
            if (! $subscriber || ! $subscriber->isConfirmed()) {
                $send->forceFill(['sent_at' => now(), 'error' => 'abgemeldet'])->save();

                continue;
            }

            try {
                Mail::to($subscriber->email)->send(new CampaignMail($campaign, $subscriber));
                $send->forceFill(['sent_at' => now(), 'error' => null])->save();
                $campaign->increment('sent_count');
            } catch (Throwable $e) {
                // One bad address must not stop the rest of the list.
                $send->forceFill([
                    'sent_at' => now(),
                    'error' => mb_substr($e->getMessage(), 0, 250),
                ])->save();
            }
        }

        // Straight back in the queue; the next cron tick picks it up.
        self::dispatch($campaign->id);
    }
}
