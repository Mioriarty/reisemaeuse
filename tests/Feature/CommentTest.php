<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPost(): Post
    {
        return Post::create([
            'title' => 'Eintrag',
            'slug' => 'eintrag',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);
    }

    public function test_a_comment_is_published_immediately(): void
    {
        $post = $this->publishedPost();

        $this->post('/blog/'.$post->slug.'/kommentare', [
            'author_name' => 'Oma',
            'body' => 'Schöne Bilder!',
        ])->assertRedirect();

        $this->assertDatabaseCount('comments', 1);

        $this->get('/blog/'.$post->slug)->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('comments', 1)
                ->where('comments.0.body', 'Schöne Bilder!')
                ->where('comments.0.authorName', 'Oma'),
        );
    }

    public function test_the_email_address_is_never_sent_to_the_page(): void
    {
        $post = $this->publishedPost();

        Comment::create([
            'post_id' => $post->id,
            'author_name' => 'Jonas',
            'author_email' => 'geheim@example.org',
            'body' => 'Hallo',
        ]);

        $this->get('/blog/'.$post->slug)
            ->assertDontSee('geheim@example.org')
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('comments', 1)
                    ->missing('comments.0.authorEmail')
                    ->missing('comments.0.ip_hash'),
            );
    }

    public function test_the_honeypot_rejects_a_bot(): void
    {
        $post = $this->publishedPost();

        $this->post('/blog/'.$post->slug.'/kommentare', [
            'author_name' => 'Bot',
            'body' => 'Kaufen Sie Pillen',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_a_flood_of_comments_is_rate_limited(): void
    {
        RateLimiter::clear('comments:'.hash('sha256', '127.0.0.1'.config('app.key')));
        $post = $this->publishedPost();

        for ($i = 0; $i < 10; $i++) {
            $this->post('/blog/'.$post->slug.'/kommentare', [
                'author_name' => 'Vielschreiber',
                'body' => 'Kommentar Nummer '.$i,
            ])->assertSessionHasNoErrors();
        }

        $this->post('/blog/'.$post->slug.'/kommentare', [
            'author_name' => 'Vielschreiber',
            'body' => 'Einer zu viel',
        ])->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 10);
    }

    public function test_a_draft_accepts_no_comments(): void
    {
        $post = Post::create([
            'title' => 'Entwurf',
            'slug' => 'entwurf',
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->post('/blog/'.$post->slug.'/kommentare', [
            'author_name' => 'Jemand',
            'body' => 'Hallo',
        ])->assertNotFound();
    }
}
