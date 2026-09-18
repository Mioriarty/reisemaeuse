<?php

namespace App\Support;

use App\Models\Media;
use App\Models\Post;
use App\Models\Setting;

/**
 * Welches Foto die Startseite ueber die volle Hoehe zeigt.
 *
 * Es gibt zwei Quellen, und beide Seiten muessen sich einig sein: die
 * Startseite zeigt das Bild, die Verwaltung sagt an, welches es waere. Deshalb
 * steht die Regel hier einmal statt zweimal.
 *
 * Ohne eigene Wahl nimmt die Seite das Aufmacherfoto des juengsten Eintrags -
 * so steht dort auch dann etwas, wenn nie jemand etwas ausgewaehlt hat.
 */
class HomeHero
{
    /** Das ausdruecklich gewaehlte Bild, falls es eines gibt. */
    public static function chosen(): ?Media
    {
        $id = Setting::get(Setting::HOME_MEDIA);

        // find() statt findOrFail(): wird das Bild spaeter geloescht, faellt
        // die Startseite auf das Aufmacherfoto zurueck statt auf einen Fehler.
        return $id === null ? null : Media::find($id);
    }

    /** Das Aufmacherfoto des juengsten veroeffentlichten Eintrags. */
    public static function fallback(): ?Media
    {
        return Post::published()
            ->whereNotNull('cover_media_id')
            ->latest('published_at')
            ->with('coverMedia')
            ->first()?->coverMedia;
    }

    public static function current(): ?Media
    {
        return static::chosen() ?? static::fallback();
    }
}
