<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * Optimasi & penyimpanan gambar Logo dan Favicon aplikasi.
 *
 * Compatible with: Intervention Image v2.x
 */
class AppAssetImageService
{
    private const DISK = 'public';
    private const DIR = 'logos';

    private ImageManager $manager;

    public function __construct()
    {
        // Inisialisasi driver GD untuk Intervention Image v2
        $this->manager = new ImageManager(['driver' => 'gd']);
    }

    /**
     * Resize max-width 400px (proporsional, tidak upscale gambar yang lebih
     * kecil dari 400px), lalu konversi ke WebP kualitas 80%.
     *
     * @return string Path relatif pada disk 'public', untuk disimpan di kolom logo.
     */
    public function storeLogo(UploadedFile $file): string
    {
        // Pengganti read() di v2
        $image = $this->manager->make($file->getRealPath());

        // Pengganti scaleDown() di v2
        $image->resize(400, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $path = self::DIR . '/logo_' . now()->timestamp . '.webp';

        // Pengganti WebpEncoder di v2
        Storage::disk(self::DISK)->put(
            $path,
            (string) $image->encode('webp', 80)
        );

        return $path;
    }

    /**
     * SVG & ICO disimpan apa adanya (format vektor/ikon native).
     * Format raster lain (PNG/JPG/WEBP) di-crop+resize persis 32x32
     * lalu dikonversi ke PNG transparan.
     *
     * @return string Path relatif pada disk 'public', untuk disimpan di kolom favicon.
     *
     * @throws RuntimeException jika SVG mengandung tag <script>.
     */
    public function storeFavicon(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'svg') {
            $this->guardSvgIsSafe($file);

            $path = self::DIR . '/favicon_' . now()->timestamp . '.svg';
            Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()));

            return $path;
        }

        if ($ext === 'ico') {
            $path = self::DIR . '/favicon_' . now()->timestamp . '.ico';
            Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()));

            return $path;
        }

        // Pengganti read() di v2
        $image = $this->manager->make($file->getRealPath());

        // Pengganti cover() di v2
        $image->fit(32, 32);

        $path = self::DIR . '/favicon_' . now()->timestamp . '.png';

        // Pengganti PngEncoder di v2
        Storage::disk(self::DISK)->put(
            $path,
            (string) $image->encode('png')
        );

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Tolak SVG yang mengandung tag <script> — pencegahan XSS dasar.
     */
    private function guardSvgIsSafe(UploadedFile $file): void
    {
        $content = file_get_contents($file->getRealPath());

        if ($content !== false && preg_match('/<script/i', $content)) {
            throw new RuntimeException('File SVG mengandung tag <script> dan ditolak demi keamanan.');
        }
    }
}