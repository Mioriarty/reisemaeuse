<?php

namespace Database\Seeders;

use App\Enums\BlockType;
use App\Models\Comment;
use App\Models\Composition;
use App\Models\Media;
use App\Models\Post;
use App\Models\Stop;
use App\Models\Subscriber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A plausible trip so every surface has realistic content while building.
 *
 * The images are generated placeholders rather than real photographs - enough
 * to see the patterns, the srcset and the colour placeholders behave.
 */
class DemoTripSeeder extends Seeder
{
    public function run(): void
    {
        $stops = $this->seedStops();
        $media = $this->seedMedia();

        $lima = $this->post(
            'Die ersten Tage in Lima',
            $stops['lima'],
            '2026-02-14 09:00',
            'Ankommen, Zeitumstellung, der erste Nebel über der Küste – und ein Klavier in einer Bar in Barranco.',
            $media,
        );

        Composition::create([
            'post_id' => $lima->id,
            'title' => 'Garúa',
            'description' => 'Ein kurzes Stück über den Küstennebel, der in Lima monatelang über der Stadt hängt.',
            ...$this->demoAudio('garua', [440.0, 523.25, 659.25, 587.33]),
            ...$this->demoScore('garua'),
        ]);

        $cusco = $this->post(
            'Über die Anden nach Cusco',
            $stops['cusco'],
            '2026-03-02 18:30',
            'Zwölf Stunden Bus, 3400 Meter Höhe und plötzlich sehr viel dünnere Luft.',
            $media,
        );

        Composition::create([
            'post_id' => $cusco->id,
            'title' => 'Tres mil cuatrocientos',
            'description' => 'Geschrieben in der ersten Nacht, in der wir wegen der Höhe nicht schlafen konnten.',
            ...$this->demoAudio('tres-mil', [329.63, 392.0, 493.88, 440.0, 329.63]),
            ...$this->demoScore('tres-mil'),
        ]);

        $uyuni = $this->post(
            'Salz, soweit man sehen kann',
            $stops['uyuni'],
            '2026-03-20 12:00',
            'Drei Tage über den größten Salzsee der Welt – und ein Horizont, der einfach nicht aufhört.',
            $media,
        );

        foreach ([$lima, $cusco, $uyuni] as $post) {
            Comment::create([
                'post_id' => $post->id,
                'author_name' => 'Oma Christa',
                'author_email' => 'oma@example.org',
                'body' => "Wunderschöne Bilder! Passt gut auf euch auf, ihr zwei.\n\nUnd die Musik habe ich mir gleich zweimal angehört.",
                'created_at' => $post->published_at?->addDay(),
            ]);
        }

        Comment::create([
            'post_id' => $lima->id,
            'author_name' => 'Jonas',
            'body' => 'Das Stück zu Lima ist großartig. Gibt es die Noten irgendwo?',
            'created_at' => $lima->published_at?->addDays(2),
        ]);

        Subscriber::create([
            'email' => 'mitleser@example.org',
            'confirmed_at' => now()->subWeek(),
        ]);
        Subscriber::create(['email' => 'wartet@example.org']);

        $this->command->info('Demo-Reise angelegt.');
    }

    /**
     * @return array<string, Stop>
     */
    private function seedStops(): array
    {
        $rows = [
            ['lima', 'Lima', 'Peru', -12.0464, -77.0428, '2026-02-10', 'Ankunft. Nebel, Ceviche, und die ersten Tage Orientierung.'],
            ['huaraz', 'Huaraz', 'Peru', -9.5278, -77.5278, '2026-02-22', null],
            ['cusco', 'Cusco', 'Peru', -13.5319, -71.9675, '2026-03-01', 'Der erste richtige Höhenschock.'],
            ['puno', 'Puno', 'Peru', -15.8402, -70.0219, '2026-03-12', null],
            ['lapaz', 'La Paz', 'Bolivien', -16.4897, -68.1193, '2026-03-16', null],
            ['uyuni', 'Uyuni', 'Bolivien', -20.4597, -66.8250, '2026-03-19', 'Salz, soweit man sehen kann.'],
            ['atacama', 'San Pedro de Atacama', 'Chile', -22.9087, -68.1997, '2026-03-25', null],
            ['salta', 'Salta', 'Argentinien', -24.7821, -65.4232, '2026-04-02', null],
            ['bariloche', 'Bariloche', 'Argentinien', -41.1335, -71.3103, '2026-04-18', null],
            ['ushuaia', 'Ushuaia', 'Argentinien', -54.8019, -68.3030, '2026-05-06', 'Das Ende der Straße.'],
        ];

        $stops = [];

        foreach ($rows as $position => [$key, $name, $country, $lat, $lng, $arrived, $note]) {
            $stops[$key] = Stop::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'country' => $country,
                'lat' => $lat,
                'lng' => $lng,
                'arrived_on' => $arrived,
                'note' => $note,
                'position' => $position,
            ]);
        }

        return $stops;
    }

    /**
     * @return list<Media>
     */
    private function seedMedia(): array
    {
        $disk = Storage::disk('public');
        $media = [];

        $palette = [
            ['#8c7a63', 4 / 3], ['#5d6b6a', 3 / 2], ['#a8886b', 1 / 1],
            ['#6b7f93', 4 / 5], ['#9c8f7a', 3 / 2], ['#7b6a5d', 4 / 3],
            ['#93826e', 1 / 1], ['#607080', 4 / 5], ['#8a7f6d', 3 / 2],
        ];

        foreach ($palette as $i => [$color, $ratio]) {
            $width = 2400;
            $height = (int) round($width / $ratio);
            $stem = 'demo/platzhalter-'.($i + 1);

            $variants = ['webp' => [], 'jpeg' => []];

            foreach ([480, 960, 1600, 2400] as $w) {
                $h = (int) round($w / $ratio);
                $svg = $this->placeholderSvg($w, $h, $color, $i + 1);
                $disk->put("{$stem}-{$w}.svg", $svg);
                // The demo files are SVG; the srcset keys still describe real
                // widths so the layout behaves exactly as it will with photos.
                $variants['webp'][$w] = "{$stem}-{$w}.svg";
                $variants['jpeg'][$w] = "{$stem}-{$w}.svg";
            }

            $disk->put("{$stem}.svg", $this->placeholderSvg($width, $height, $color, $i + 1));

            $media[] = Media::create([
                'path' => "{$stem}.svg",
                'original_name' => 'platzhalter-'.($i + 1).'.svg',
                'mime' => 'image/svg+xml',
                'size' => 1024,
                'width' => $width,
                'height' => $height,
                'aspect_ratio' => round($ratio, 4),
                'dominant_color' => $color,
                'alt' => 'Platzhalterbild '.($i + 1),
                'caption' => null,
                'variants' => $variants,
            ]);
        }

        return $media;
    }

    /**
     * A few synthesised notes as a real WAV file, so the player on the demo
     * entries actually plays something instead of sitting there greyed out.
     *
     * @param  list<float>  $notes  frequencies in Hz, one per beat
     * @return array<string, mixed>
     */
    private function demoAudio(string $name, array $notes): array
    {
        $rate = 11025;
        $beat = 0.55;
        $samples = '';

        foreach ($notes as $freq) {
            $length = (int) ($rate * $beat);
            for ($i = 0; $i < $length; $i++) {
                // Fade each note out so the sequence does not click.
                $envelope = 1.0 - ($i / $length);
                $value = (int) (9000 * $envelope * sin(2 * M_PI * $freq * $i / $rate));
                $samples .= pack('v', $value < 0 ? $value + 65536 : $value);
            }
        }

        $header = 'RIFF'.pack('V', 36 + strlen($samples)).'WAVEfmt '
            .pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16)
            .'data'.pack('V', strlen($samples));

        $path = 'compositions/audio/demo-'.$name.'.wav';
        Storage::disk('public')->put($path, $header.$samples);

        return [
            'audio_path' => $path,
            'duration_seconds' => (int) ceil(count($notes) * $beat),
        ];
    }

    /**
     * A placeholder score so the Noten section of the player has something to
     * show. Replaced by a real PDF as soon as one is uploaded.
     *
     * @return array<string, mixed>
     */
    private function demoScore(string $name): array
    {
        $lines = '';
        foreach ([0, 1, 2, 3, 4] as $i) {
            $y = 30 + $i * 12;
            $lines .= '<line x1="30" y1="'.$y.'" x2="770" y2="'.$y.'" stroke="#141414" stroke-width="1"/>';
        }

        $notes = '';
        foreach ([120, 190, 260, 330, 400, 470, 540, 610] as $i => $x) {
            $y = 30 + (($i * 3) % 5) * 12;
            $notes .= '<rect x="'.$x.'" y="'.($y - 4).'" width="10" height="8" fill="#141414"/>'
                .'<line x1="'.($x + 10).'" y1="'.($y - 4).'" x2="'.($x + 10).'" y2="'.($y - 34).'" stroke="#141414" stroke-width="2"/>';
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 120" width="800" height="120">'
            .'<rect width="100%" height="100%" fill="#ffffff"/>'.$lines.$notes.'</svg>';

        $path = 'compositions/scores/demo-'.$name.'.svg';
        Storage::disk('public')->put($path, $svg);

        return ['score_path' => $path, 'score_mime' => 'image/svg+xml'];
    }

    private function placeholderSvg(int $w, int $h, string $color, int $n): string
    {
        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" width="{$w}" height="{$h}">
            <rect width="100%" height="100%" fill="{$color}"/>
            <text x="50%" y="50%" fill="#ffffff" fill-opacity="0.45" font-family="sans-serif"
                  font-size="{$w}" font-weight="700" text-anchor="middle" dominant-baseline="central"
                  transform="scale(0.12)" transform-origin="center">{$n}</text>
        </svg>
        SVG;
    }

    /**
     * @param  list<Media>  $media
     */
    private function post(string $title, Stop $stop, string $publishedAt, string $excerpt, array $media): Post
    {
        $post = Post::create([
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $excerpt,
            'stop_id' => $stop->id,
            'cover_media_id' => $media[0]->id,
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => $publishedAt,
            'reading_minutes' => 4,
        ]);

        // Every one of the eight patterns appears, so the demo exercises the
        // whole renderer rather than only the easy cases.
        $blocks = [
            [BlockType::Text, ['html' => '<p>Wir sind angekommen. Der Bus hat sechs Stunden länger gebraucht als angekündigt, und trotzdem war es das gute Sechs-Stunden-Länger: die Straße lief zuletzt an einem Fluss entlang, und irgendwann hörte das Grün auf.</p><p>Auf dem Markt haben wir erst einmal <strong>alles</strong> gekauft, was wir nicht kannten.</p>']],
            [BlockType::ImageFull, ['media_id' => $media[1]->id, 'caption' => 'Der erste Morgen, kurz nach sechs.', 'bleed' => true]],
            [BlockType::Heading, ['text' => 'Was uns niemand gesagt hatte', 'label' => 'Kapitel zwei']],
            [BlockType::ImageText, ['media_id' => $media[2]->id, 'html' => '<p>Dass es nachts so kalt wird, zum Beispiel. Und dass man die ersten zwei Tage einfach nur sitzt und atmet.</p><p>Dafür ist das Licht am Nachmittag so, dass man ständig stehen bleibt.</p>', 'variant' => 'left', 'caption' => 'Nachmittagslicht.']],
            [BlockType::ImagePair, ['left_media_id' => $media[3]->id, 'right_media_id' => $media[4]->id, 'caption' => 'Links der Weg hinauf, rechts der Blick zurück.']],
            [BlockType::Quote, ['text' => 'Man reist nicht, um anzukommen, sondern um zu reisen.', 'attribution' => 'Goethe, angeblich']],
            [BlockType::Gallery, ['media_ids' => [$media[5]->id, $media[6]->id, $media[7]->id], 'caption' => 'Drei Tage in Bildern.']],
            [BlockType::Divider, ['glyph' => '✳']],
            [BlockType::Text, ['html' => '<p>Morgen geht es weiter. Das Stück oben ist hier entstanden, abends auf der Dachterrasse, mit einem sehr verstimmten Klavier.</p>']],
        ];

        foreach ($blocks as $position => [$type, $data]) {
            $post->blocks()->create([
                'type' => $type,
                'position' => $position,
                'data' => $data,
            ]);
        }

        return $post;
    }
}
