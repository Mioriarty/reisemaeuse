<?php

namespace App\Http\Controllers;

use App\Models\Stop;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    public function __invoke(): Response
    {
        $stops = Stop::with(['posts' => fn ($query) => $query->published()->latest('published_at')])
            ->orderBy('position')
            ->get();

        Seo::set('Die Route', 'Alle Stationen unserer Reise durch Südamerika auf einer Karte.');

        return Inertia::render('Reise', [
            'stops' => $stops->map(fn (Stop $stop) => [
                ...$stop->toMapProps(),
                'note' => $stop->note,
                'posts' => $stop->posts->map(fn ($post) => [
                    'title' => $post->title,
                    'slug' => $post->slug,
                ])->all(),
            ])->all(),
        ]);
    }
}
