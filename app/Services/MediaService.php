<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Analyzers\DominantPaletteAnalyzer;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Throwable;

class MediaService
{
    /**
     * The widths we generate. A phone fetches the 480 file, a 5K display the
     * 2400 one - the srcset on <img> picks. Anything wider than the original
     * is skipped rather than upscaled.
     *
     * @var list<int>
     */
    public const WIDTHS = [480, 960, 1600, 2400];

    private ImageManager $manager;

    public function __construct()
    {
        // GD rather than Imagick: GD is the one that is reliably compiled in
        // on netcup shared hosting.
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Store an upload and generate every variant.
     *
     * Deliberately synchronous: the queue on shared hosting only drains once a
     * minute via cron, and the admin should not have to wait a minute to see
     * the picture they just dropped in.
     */
    public function store(UploadedFile $file, ?string $alt = null, ?string $caption = null): Media
    {
        $disk = Storage::disk('public');
        $stem = Str::random(8).'-'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $stem = Str::limit($stem, 60, '');
        $dir = 'media/'.date('Y/m');

        $image = $this->manager->decodePath($file->getRealPath());
        // Phones write orientation into EXIF rather than rotating pixels.
        $image->orient();

        // Everything is normalised to JPEG so the pipeline has one input format.
        $originalPath = $dir.'/'.$stem.'.jpg';
        $disk->put($originalPath, (string) $image->encode(new JpegEncoder(quality: 92)));

        $variants = $this->generateVariants($image, $dir, $stem);

        return Media::create([
            'path' => $originalPath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => 'image/jpeg',
            'size' => $disk->size($originalPath),
            'width' => $image->width(),
            'height' => $image->height(),
            'aspect_ratio' => $image->height() > 0 ? round($image->width() / $image->height(), 4) : 1,
            'dominant_color' => $this->dominantColor($image),
            'alt' => $alt,
            'caption' => $caption,
            'taken_at' => $this->takenAt($image),
            'variants' => $variants,
        ]);
    }

    /**
     * @return array{webp: array<int, string>, jpeg: array<int, string>}
     */
    private function generateVariants(ImageInterface $image, string $dir, string $stem): array
    {
        $disk = Storage::disk('public');
        $variants = ['webp' => [], 'jpeg' => []];

        foreach (self::WIDTHS as $width) {
            if ($width > $image->width()) {
                continue;
            }

            $resized = (clone $image)->scaleDown(width: $width);

            $webpPath = $dir.'/'.$stem.'-'.$width.'.webp';
            $disk->put($webpPath, (string) $resized->encode(new WebpEncoder(quality: 82)));
            $variants['webp'][$width] = $webpPath;

            $jpegPath = $dir.'/'.$stem.'-'.$width.'.jpg';
            $disk->put($jpegPath, (string) $resized->encode(new JpegEncoder(quality: 82)));
            $variants['jpeg'][$width] = $jpegPath;
        }

        // A very small original still needs at least one variant to point at.
        if ($variants['webp'] === []) {
            $webpPath = $dir.'/'.$stem.'-'.$image->width().'.webp';
            $disk->put($webpPath, (string) $image->encode(new WebpEncoder(quality: 82)));
            $variants['webp'][$image->width()] = $webpPath;

            $jpegPath = $dir.'/'.$stem.'-'.$image->width().'.jpg';
            $disk->put($jpegPath, (string) $image->encode(new JpegEncoder(quality: 82)));
            $variants['jpeg'][$image->width()] = $jpegPath;
        }

        return $variants;
    }

    /**
     * The colour the layout paints into the image box while the file loads.
     */
    private function dominantColor(ImageInterface $image): string
    {
        try {
            // Der Analyzer liefert bereits eine RGB-Farbe. Ein convertTo() darauf
            // gibt es in Intervention 4 nicht - der Aufruf landete frueher immer
            // im catch, und jedes Foto bekam die neutrale Ausweichfarbe.
            $color = $image->analyze(new DominantPaletteAnalyzer(limit: 1))->first();

            return $color ? '#'.ltrim($color->toHex(), '#') : '#e6e4de';
        } catch (Throwable) {
            return '#e6e4de';
        }
    }

    private function takenAt(ImageInterface $image): ?string
    {
        try {
            $raw = $image->exif('DateTimeOriginal');
            if (! is_string($raw) || $raw === '') {
                return null;
            }

            return \DateTimeImmutable::createFromFormat('Y:m:d H:i:s', $raw)?->format('Y-m-d H:i:s') ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Remove a media record and every file it owns.
     */
    public function delete(Media $media): void
    {
        $disk = Storage::disk('public');
        $disk->delete($media->path);

        foreach ($media->variants ?? [] as $paths) {
            foreach ($paths as $path) {
                $disk->delete($path);
            }
        }

        $media->delete();
    }
}
