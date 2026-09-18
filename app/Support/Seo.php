<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Collects the per-page document head.
 *
 * Inertia renders the body on the client, so anything a crawler or a WhatsApp
 * link preview needs has to be in the initial HTML. Controllers call
 * Seo::set(...) and the root Blade template prints it.
 */
class Seo
{
    public static function set(
        string $title,
        ?string $description = null,
        ?string $image = null,
        string $type = 'website',
        ?string $publishedAt = null,
        bool $noindex = false,
    ): void {
        /** @var Request $request */
        $request = request();

        $request->attributes->set('seo', array_filter([
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'type' => $type,
            'publishedAt' => $publishedAt,
            'url' => $request->fullUrl(),
            // array_filter wirft das false gleich wieder raus - genau richtig,
            // gefragt wird ohnehin nur, ob der Schluessel gesetzt ist.
            'noindex' => $noindex,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        return request()->attributes->get('seo', []);
    }
}
