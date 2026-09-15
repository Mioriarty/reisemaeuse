<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Stop;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::published()
            ->with(['stop', 'coverMedia', 'composition'])
            ->latest('published_at')
            ->take(4)
            ->get();

        $stops = Stop::orderBy('position')->get();

        Seo::set(
            title: 'Wandermäuse',
            description: 'Ein Reiseblog über unsere Reise durch Südamerika – mit Karte, Bildern und einer kleinen Komposition zu jedem Eintrag.',
            image: $posts->first()?->coverMedia?->url(),
        );

        return Inertia::render('Home', [
            'posts' => $posts->map->toCardProps()->all(),
            'stops' => $stops->map->toMapProps()->all(),
            'intro' => [
                'kilometres' => null,
                'countries' => $stops->pluck('country')->unique()->count(),
                'stopCount' => $stops->count(),
            ],
        ]);
    }
}
