<?php

namespace Tests\Feature;

use Tests\TestCase;

class FontDeliveryTest extends TestCase
{
    /**
     * Die Schriften muessen in der Vorlage verlinkt werden.
     *
     * Ohne diesen Aufruf baut Vite die Schriftdateien zwar und legt sie samt
     * fonts-manifest.json ab, verlinkt sie aber nirgends. Die Seite faellt dann
     * still auf `ui-serif` zurueck - still, weil das auf jedem Geraet nach
     * einer Serife aussieht, nur eben nach einer anderen. Genau so ist es
     * einmal unbemerkt live gegangen.
     *
     * Geprueft wird die Vorlage und nicht die gerenderte Seite: `withoutVite()`
     * in Tests\TestCase schaltet Vite ab, damit `php artisan test` ohne ein
     * vorheriges `npm run build` laeuft.
     */
    public function test_the_layout_links_the_self_hosted_fonts(): void
    {
        $blade = file_get_contents(resource_path('views/app.blade.php'));

        $this->assertStringContainsString('Vite::fonts()', $blade);
    }

    /**
     * Jede in vite.config.ts deklarierte Familie muss auch im Design landen -
     * sonst laedt die Seite eine Schrift, die niemand benutzt, oder benutzt
     * eine, die niemand laedt.
     */
    public function test_every_declared_family_is_used_in_the_stylesheet(): void
    {
        $config = file_get_contents(base_path('vite.config.ts'));
        $css = file_get_contents(resource_path('css/app.css'));

        preg_match_all("/bunny\('([^']+)'/", $config, $matches);
        $families = $matches[1];

        $this->assertNotEmpty($families, 'In vite.config.ts ist keine Schrift deklariert.');

        foreach ($families as $family) {
            $this->assertStringContainsString(
                "'{$family}'",
                $css,
                "Die Schrift {$family} wird geladen, aber in app.css nie benutzt.",
            );
        }
    }
}
