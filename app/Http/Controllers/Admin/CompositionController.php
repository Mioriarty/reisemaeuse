<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Composition;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompositionController extends Controller
{
    public function save(Request $request, Post $post): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:36000'],
            'audio' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp4,audio/ogg,audio/wav,audio/x-m4a', 'max:40960'],
            'score' => ['nullable', 'file', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:20480'],
        ], [
            'audio.mimetypes' => 'Bitte eine Audiodatei hochladen (MP3, M4A, OGG oder WAV).',
            'score.mimetypes' => 'Die Noten müssen ein PDF, JPG oder PNG sein.',
        ]);

        $composition = $post->composition ?? new Composition(['post_id' => $post->id]);

        $composition->title = $validated['title'];
        $composition->description = $validated['description'] ?? null;
        $composition->duration_seconds = $validated['duration_seconds'] ?? $composition->duration_seconds ?? 0;

        if ($file = $request->file('audio')) {
            $this->forget($composition->audio_path);
            $composition->audio_path = $file->store('compositions/audio', 'public');
        }

        if ($file = $request->file('score')) {
            $this->forget($composition->score_path);
            $composition->score_path = $file->store('compositions/scores', 'public');
            $composition->score_mime = $file->getClientMimeType();
        }

        $composition->post_id = $post->id;
        $composition->save();

        return back()->with('success', 'Komposition gespeichert.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        if ($composition = $post->composition) {
            $this->forget($composition->audio_path);
            $this->forget($composition->score_path);
            $composition->delete();
        }

        return back()->with('success', 'Komposition entfernt.');
    }

    private function forget(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
