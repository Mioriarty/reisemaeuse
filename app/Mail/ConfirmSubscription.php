<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmSubscription extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bitte bestätige deine Anmeldung');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.confirm-subscription',
            with: [
                'confirmUrl' => route('newsletter.confirm', $this->subscriber->token),
            ],
        );
    }
}
