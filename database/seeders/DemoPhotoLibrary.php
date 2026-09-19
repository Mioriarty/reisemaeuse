<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Echte Landschaftsfotos fuer die Demo-Reise.
 *
 * Auf farbigen Platzhaltern laesst sich eine fotolastige Seite nicht beurteilen
 * - Bildausschnitte, Dominanzfarben und die Schrift ueber dem Titelbild sehen
 * mit echten Fotos ganz anders aus. Die Dateien liegen auf Wikimedia Commons
 * (frei lizenziert, Dateinamen siehe unten) und werden einmal heruntergeladen,
 * unter storage/app/private/demo-photos zwischengespeichert und danach von dort
 * gelesen. Sie sind reines Entwicklungsmaterial: der Ordner ist aus dem Git
 * ausgenommen, und in Produktion stehen dort die echten Uploads.
 *
 * Sie laufen durch denselben MediaService wie ein Upload aus der Verwaltung,
 * erzeugen also echte WebP- und JPEG-Varianten, echte Seitenverhaeltnisse und
 * echte Dominanzfarben.
 *
 * Ohne Netz gibt fetch() null zurueck, und der Seeder faellt auf die alten
 * Platzhalter zurueck - Seeden darf nie daran scheitern, dass Commons gerade
 * nicht erreichbar ist.
 */
class DemoPhotoLibrary
{
    private const CACHE_DIR = 'demo-photos';

    private const SOURCE = 'https://commons.wikimedia.org/wiki/Special:FilePath/';

    /** Commons verlangt eine sprechende Kennung statt des Standard-Agenten. */
    private const USER_AGENT = 'wandermaeuse-dev/1.0 (lokale Demo-Daten; kein Bot)';

    /**
     * Entlang der Route, damit die Demo-Eintraege zu ihren Stationen passen.
     * Bewusst gemischt hoch und quer: so zeigt sich, ob die Zuschnitte in der
     * Schiene, im Paar und in der Galerie wirklich tragen.
     *
     * @var list<array{slug: string, file: string, alt: string}>
     */
    public const PHOTOS = [
        [
            'slug' => 'lima-kueste',
            'file' => 'Lima Peru coast.jpg',
            'alt' => 'Die Küste von Lima, im Dunst hinter der Brandung die Hochhäuser von Miraflores.',
        ],
        [
            'slug' => 'costa-verde',
            'file' => 'Costa Verde Cliffs.jpg',
            'alt' => 'Die steilen Klippen der Costa Verde fallen direkt zum Pazifik ab.',
        ],
        [
            'slug' => 'laguna-69',
            'file' => 'Laguna 69 en Perú.jpg',
            'alt' => 'Der türkise Bergsee Laguna 69 unter den Schneefeldern der Cordillera Blanca.',
        ],
        [
            'slug' => 'machu-picchu',
            'file' => 'Machu Picchu and Waynapicchu (Landscape).jpg',
            'alt' => 'Machu Picchu am Vormittag, dahinter der Huayna Picchu.',
        ],
        [
            'slug' => 'titicaca',
            'file' => 'Amanecer en el lago Titicaca, Puno, Perú, 2015-08-01, DD 01.JPG',
            'alt' => 'Sonnenaufgang über dem Titicacasee bei Puno.',
        ],
        [
            'slug' => 'la-paz',
            'file' => 'La Paz city with Illimani, Bolivia.jpg',
            'alt' => 'La Paz zieht sich den Hang hinauf, darüber der vereiste Illimani.',
        ],
        [
            'slug' => 'uyuni-spiegel',
            'file' => 'Reflection on the Salar de Uyuni, bolivia.jpg',
            'alt' => 'Eine dünne Wasserschicht auf dem Salar de Uyuni spiegelt den Himmel.',
        ],
        [
            'slug' => 'uyuni-insel',
            'file' => 'Isla del Pescado, Salar de Uyuni, Bolivia, 2016-02-04, DD 42.JPG',
            'alt' => 'Die Kakteeninsel Isla del Pescado mitten in der weißen Salzfläche.',
        ],
        [
            'slug' => 'valle-de-la-luna',
            'file' => 'Valle de la Luna, San Pedro de Atacama, Chile, 2016-02-01, DD 160.JPG',
            'alt' => 'Zerfurchte Sand- und Salzrücken im Valle de la Luna bei San Pedro de Atacama.',
        ],
        [
            'slug' => 'humahuaca',
            'file' => 'Quebrada de Humahuaca 02.jpg',
            'alt' => 'Die farbig geschichteten Hänge der Quebrada de Humahuaca.',
        ],
    ];

    public function __construct(private readonly MediaService $media) {}

    /**
     * Laedt ein Foto (oder nimmt die zwischengespeicherte Datei) und legt es
     * als Media an. null heisst: nicht erreichbar, nimm den Platzhalter.
     *
     * @param  array{slug: string, file: string, alt: string}  $photo
     */
    public function fetch(array $photo): ?Media
    {
        $path = $this->cached($photo);

        if ($path === null) {
            return null;
        }

        try {
            // $test = true: die Datei kommt nicht aus einem Upload, sondern von
            // der Platte - sonst weigert sich UploadedFile, sie anzufassen.
            $file = new UploadedFile($path, $photo['slug'].'.jpg', 'image/jpeg', null, true);

            return $this->media->store($file, $photo['alt']);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Der Pfad zur lokalen Kopie, notfalls frisch geladen.
     *
     * @param  array{slug: string, file: string, alt: string}  $photo
     */
    private function cached(array $photo): ?string
    {
        $disk = Storage::disk('local');
        $relative = self::CACHE_DIR.'/'.$photo['slug'].'.jpg';

        if ($disk->exists($relative) && $disk->size($relative) > 0) {
            return $disk->path($relative);
        }

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(60)
                ->get(self::SOURCE.rawurlencode(str_replace(' ', '_', $photo['file'])), ['width' => 2400]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            return null;
        }

        $disk->put($relative, $response->body());

        return $disk->path($relative);
    }
}
