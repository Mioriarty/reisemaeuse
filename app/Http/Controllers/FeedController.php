<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function rss(): Response
    {
        $posts = Post::published()->with('coverMedia')->latest('published_at')->take(30)->get();

        return response()
            ->view('feeds.rss', ['posts' => $posts])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $posts = Post::published()->latest('published_at')->get();

        return response()
            ->view('feeds.sitemap', ['posts' => $posts])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
