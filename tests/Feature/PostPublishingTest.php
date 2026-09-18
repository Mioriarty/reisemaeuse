<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function unpublishedStates(): array
    {
        return [
            'Entwurf ohne Datum' => [Post::STATUS_DRAFT, null],
            'Entwurf mit Datum' => [Post::STATUS_DRAFT, '-1 hour'],
            'geplant' => [Post::STATUS_SCHEDULED, '+1 day'],
            'veroeffentlicht, aber noch nicht faellig' => [Post::STATUS_PUBLISHED, '+1 day'],
        ];
    }

    #[DataProvider('unpublishedStates')]
    public function test_an_admin_can_preview_an_unpublished_post(string $status, ?string $when): void
    {
        $post = Post::create([
            'title' => 'Noch nicht so weit',
            'slug' => 'noch-nicht',
            'status' => $status,
            'published_at' => $when === null ? null : new \DateTimeImmutable($when),
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('isPreview', true));
    }

    #[DataProvider('unpublishedStates')]
    public function test_a_guest_still_gets_nothing(string $status, ?string $when): void
    {
        $post = Post::create([
            'title' => 'Noch nicht so weit',
            'slug' => 'noch-nicht',
            'status' => $status,
            'published_at' => $when === null ? null : new \DateTimeImmutable($when),
        ]);

        $this->get('/blog/'.$post->slug)->assertNotFound();
    }

    public function test_a_preview_is_marked_noindex(): void
    {
        $post = Post::create([
            'title' => 'Entwurf',
            'slug' => 'entwurf-noindex',
            'status' => Post::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    public function test_a_published_post_is_not_a_preview_and_stays_indexable(): void
    {
        $post = Post::create([
            'title' => 'Live',
            'slug' => 'live',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertDontSee('noindex', false)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('isPreview', false));
    }

    /**
     * Die Vorschau oeffnet die oeffentliche Seite - sie darf den Entwurf aber
     * nirgends sonst auftauchen lassen, auch nicht fuer die angemeldete Person.
     */
    public function test_a_preview_does_not_leak_the_draft_into_listings(): void
    {
        Post::create([
            'title' => 'Entwurf',
            'slug' => 'entwurf-liste',
            'status' => Post::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/blog')->assertInertia(
            fn (AssertableInertia $page) => $page->where('total', 0),
        );
        $this->actingAs($admin)->get('/feed.xml')->assertDontSee('entwurf-liste');
        $this->actingAs($admin)->get('/sitemap.xml')->assertDontSee('entwurf-liste');
    }

    public function test_a_draft_without_a_date_has_no_neighbours(): void
    {
        Post::create([
            'title' => 'Vorher',
            'slug' => 'vorher',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subWeek(),
        ]);

        $draft = Post::create([
            'title' => 'Entwurf',
            'slug' => 'entwurf-nachbarn',
            'status' => Post::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/blog/'.$draft->slug)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('neighbours.previous', null)
                ->where('neighbours.next', null));
    }
}
