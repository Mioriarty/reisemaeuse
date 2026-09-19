<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Comments', [
            'comments' => Comment::with('post')->latest()->paginate(50)->through(fn (Comment $c) => [
                'id' => $c->id,
                'authorName' => $c->author_name,
                'authorEmail' => $c->author_email,
                'body' => $c->body,
                'createdAt' => $c->created_at?->toIso8601String(),
                'post' => $c->post ? ['title' => $c->post->title, 'slug' => $c->post->slug] : null,
            ]),
        ]);
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('success', 'Kommentar gelöscht.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = Comment::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', $count.' Kommentare gelöscht.');
    }
}
