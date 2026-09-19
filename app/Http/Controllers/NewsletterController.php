<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmSubscription;
use App\Models\Subscriber;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterController extends Controller
{
    public function index(): Response
    {
        Seo::set('Newsletter', 'Eine kurze Nachricht, sobald ein neuer Eintrag online ist.');

        return Inertia::render('Newsletter/Index');
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:180'],
            'name' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'size:0'],
        ], [
            'email.required' => 'Bitte gib eine E-Mail-Adresse an.',
            'email.email' => 'Diese E-Mail-Adresse sieht nicht richtig aus.',
            'website.size' => 'Die Anmeldung konnte nicht gesendet werden.',
        ]);

        $key = 'newsletter:'.hash('sha256', $request->ip().config('app.key'));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Zu viele Versuche. Bitte probiere es später noch einmal.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        $subscriber = Subscriber::firstOrNew(['email' => strtolower($validated['email'])]);

        // An address that is already confirmed gets the same answer as a new
        // one: the form must not reveal who is on the list.
        if ($subscriber->exists && $subscriber->isConfirmed()) {
            return back()->with('success', 'Fast geschafft – schau in dein Postfach.');
        }

        $wasNew = ! $subscriber->exists;

        $subscriber->name = $validated['name'] ?? $subscriber->name;
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        try {
            Mail::to($subscriber->email)->send(new ConfirmSubscription($subscriber));
        } catch (\Throwable $e) {
            // A broken outbound mailbox must not turn into a 500 for the
            // visitor. A row we just created is rolled back so a later attempt
            // starts clean; one that already existed stays untouched.
            report($e);

            if ($wasNew) {
                $subscriber->delete();
            }

            throw ValidationException::withMessages([
                'email' => 'Die Anmeldung hat gerade nicht geklappt. Bitte versuche es in ein paar Minuten noch einmal.',
            ]);
        }

        return back()->with('success', 'Fast geschafft – schau in dein Postfach.');
    }

    public function confirm(string $token): Response
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if (! $subscriber) {
            return $this->status(
                'Link nicht gültig',
                'Dieser Bestätigungslink ist uns unbekannt. Melde dich gern noch einmal an.',
                ok: false,
            );
        }

        if (! $subscriber->confirmed_at) {
            $subscriber->forceFill([
                'confirmed_at' => now(),
                'unsubscribed_at' => null,
            ])->save();
        }

        return $this->status(
            'Du bist dabei',
            'Deine Adresse ist bestätigt. Wir melden uns, sobald es etwas Neues gibt.',
            ok: true,
        );
    }

    public function unsubscribe(string $token): Response
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if (! $subscriber) {
            return $this->status(
                'Link nicht gültig',
                'Diesen Abmeldelink kennen wir nicht. Melde dich gern direkt bei uns.',
                ok: false,
            );
        }

        $subscriber->forceFill(['unsubscribed_at' => now()])->save();

        return $this->status(
            'Abgemeldet',
            'Du bekommst keine weiteren E-Mails von uns. Schade – und gute Reise!',
            ok: true,
        );
    }

    private function status(string $heading, string $message, bool $ok): Response
    {
        Seo::set($heading);

        return Inertia::render('Newsletter/Status', compact('heading', 'message', 'ok'));
    }
}
