<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'author_name' => ['required', 'string', 'min:2', 'max:80'],
            'author_email' => ['nullable', 'email', 'max:180'],
            'body' => ['required', 'string', 'min:2', 'max:4000'],
            'website' => ['nullable', 'size:0'],
        ], [
            'author_name.required' => 'Bitte sag uns, wie du heißt.',
            'body.required' => 'Der Kommentar ist noch leer.',
            'body.max' => 'Das ist etwas lang – bitte kürze auf 4000 Zeichen.',
            'website.size' => 'Die Nachricht konnte nicht gesendet werden.',
        ]);

        $ipHash = hash('sha256', $request->ip().config('app.key'));

        // Ten comments an hour from one address is plenty for a family blog
        // and stops a script from filling the page.
        $key = 'comments:'.$ipHash;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'body' => 'Du hast gerade viele Kommentare geschrieben. Bitte versuch es später noch einmal.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        Comment::create([
            'post_id' => $post->id,
            'author_name' => $validated['author_name'],
            'author_email' => $validated['author_email'] ?? null,
            'body' => $validated['body'],
            'ip_hash' => $ipHash,
        ]);

        return back()->with('success', 'Danke für deinen Kommentar.');
    }
}
