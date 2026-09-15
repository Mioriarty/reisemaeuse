<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PostPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_post_is_visible(): void
    {
        $post = Post::create([
            'title' => 'Sichtbar',
            'slug' => 'sichtbar',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        $this->get('/blog/'.$post->slug)->assertOk();

        $this->get('/blog')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('total', 1)
                ->where('groups.0.posts.0.title', 'Sichtbar'),
        );
    }

    public function test_a_draft_is_not_reachable(): void
    {
        $post = Post::create([
            'title' => 'Entwurf',
            'slug' => 'entwurf',
            'status' => Post::STATUS_DRAFT,
            'published_at' => now()->subHour(),
        ]);

        $this->get('/blog/'.$post->slug)->assertNotFound();
    }

    public function test_a_post_dated_in_the_future_is_not_reachable_yet(): void
    {
        $post = Post::create([
            'title' => 'Später',
            'slug' => 'spaeter',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->addDay(),
        ]);

        $this->get('/blog/'.$post->slug)->assertNotFound();
    }

    public function test_a_published_post_without_a_date_is_not_reachable(): void
    {
        $post = Post::create([
            'title' => 'Ohne Datum',
            'slug' => 'ohne-datum',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => null,
        ]);

        $this->get('/blog/'.$post->slug)->assertNotFound();
    }
}
