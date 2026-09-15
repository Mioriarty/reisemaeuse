<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public Subscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    public function content(): Content
    {
        $post = $this->campaign->post;

        return new Content(
            markdown: 'mail.campaign',
            with: [
                'intro' => $this->campaign->intro,
                'post' => $post,
                'postUrl' => $post ? url('/blog/'.$post->slug) : null,
                // Every campaign carries a working one-click unsubscribe.
                'unsubscribeUrl' => route('newsletter.unsubscribe', $this->subscriber->token),
            ],
        );
    }
}
