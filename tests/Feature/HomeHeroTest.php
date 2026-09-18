<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HomeHeroTest extends TestCase
{
    use RefreshDatabase;

    private function media(string $name): Media
    {
        return Media::create([
            'path' => "media/{$name}.jpg",
            'original_name' => "{$name}.jpg",
            'mime' => 'image/jpeg',
            'size' => 1024,
            'width' => 2400,
            'height' => 1600,
            'aspect_ratio' => 1.5,
            'dominant_color' => '#7b8870',
            'alt' => $name,
            'variants' => ['webp' => [], 'jpeg' => []],
        ]);
    }

    private function publishedPostWithCover(Media $cover, string $title = 'Eintrag'): Post
    {
        return Post::create([
            'title' => $title,
            'slug' => str($title)->slug()->value(),
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
            'cover_media_id' => $cover->id,
        ]);
    }

    public function test_without_a_choice_the_newest_cover_is_used(): void
    {
        $alt = $this->media('alt');
        $neu = $this->media('neu');

        $this->publishedPostWithCover($alt, 'Alter Eintrag')
            ->update(['published_at' => now()->subWeek()]);
        $this->publishedPostWithCover($neu, 'Neuer Eintrag');

        $this->get('/')->assertInertia(
            fn (AssertableInertia $page) => $page->where('hero.id', $neu->id),
        );
    }

    public function test_a_chosen_picture_wins_over_the_newest_cover(): void
    {
        $cover = $this->media('aufmacher');
        $gewaehlt = $this->media('gewaehlt');
        $this->publishedPostWithCover($cover);

        Setting::set(Setting::HOME_MEDIA, (string) $gewaehlt->id);

        $this->get('/')->assertInertia(
            fn (AssertableInertia $page) => $page->where('hero.id', $gewaehlt->id),
        );
    }

    /**
     * Wird das gewaehlte Bild in der Bilderverwaltung geloescht, zeigt die
     * Einstellung ins Leere. Die Startseite muss dann auf das Aufmacherfoto
     * zurueckfallen statt mit einem Fehler zu antworten.
     */
    public function test_a_deleted_choice_falls_back_instead_of_breaking(): void
    {
        $cover = $this->media('aufmacher');
        $gewaehlt = $this->media('gewaehlt');
        $this->publishedPostWithCover($cover);

        Setting::set(Setting::HOME_MEDIA, (string) $gewaehlt->id);
        $gewaehlt->delete();

        $this->get('/')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->where('hero.id', $cover->id),
        );
    }

    public function test_without_any_picture_the_page_still_opens(): void
    {
        $this->get('/')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->where('hero', null),
        );
    }

    public function test_an_admin_can_choose_and_reset_the_picture(): void
    {
        $admin = User::factory()->create();
        $bild = $this->media('titelbild');

        $this->actingAs($admin)
            ->put('/admin/startseite', ['media_id' => $bild->id])
            ->assertRedirect();

        $this->assertSame((string) $bild->id, Setting::get(Setting::HOME_MEDIA));

        $this->actingAs($admin)
            ->put('/admin/startseite', ['media_id' => null])
            ->assertRedirect();

        $this->assertNull(Setting::get(Setting::HOME_MEDIA));
    }

    public function test_an_unknown_picture_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/admin/startseite', ['media_id' => 9999])
            ->assertSessionHasErrors('media_id');
    }

    public function test_a_guest_cannot_change_the_picture(): void
    {
        $bild = $this->media('titelbild');

        $this->put('/admin/startseite', ['media_id' => $bild->id])
            ->assertRedirect('/admin/login');

        $this->assertNull(Setting::get(Setting::HOME_MEDIA));
    }
}
