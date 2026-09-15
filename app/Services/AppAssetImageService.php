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

    // AUDIT § A3: batas dimensi favicon raster sebelum diproses ImageManager,
    // sebagai lapis pertahanan terhadap decompression bomb (file kecil dengan
    // dimensi ekstrem). Tidak berlaku untuk .ico — lihat storeFavicon().
    private const MAX_FAVICON_DIMENSION = 512;

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
     * AUDIT § A3: dukungan SVG dihapus total — favicon hanya menerima PNG/ICO.
     * ICO disimpan apa adanya (format ikon native, tidak bisa diproses
     * ImageManager). PNG (dan format raster lain yang lolos validasi mimes di
     * Controller) di-crop+resize persis 32x32 lalu dikonversi ke PNG transparan,
     * didahului validasi dimensi sumber untuk mencegah decompression bomb.
     *
     * @return string Path relatif pada disk 'public', untuk disimpan di kolom favicon.
     *
     * @throws RuntimeException jika dimensi gambar melebihi batas maksimal.
     */
    public function storeFavicon(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'ico') {
            $path = self::DIR . '/favicon_' . now()->timestamp . '.ico';
            Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()));

            return $path;
        }

        $this->guardDimensionWithinLimit($file);

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
     * AUDIT § A3: tolak gambar dengan dimensi melebihi batas maksimal, sebelum
     * dibebankan ke ImageManager (mitigasi decompression bomb — file kecil
     * secara ukuran tapi dimensi piksel ekstrem).
     */
    private function guardDimensionWithinLimit(UploadedFile $file): void
    {
        $dimensions = getimagesize($file->getRealPath());

        if ($dimensions === false) {
            throw new RuntimeException('File favicon tidak dapat dibaca sebagai gambar.');
        }

        [$width, $height] = $dimensions;

        if ($width > self::MAX_FAVICON_DIMENSION || $height > self::MAX_FAVICON_DIMENSION) {
            throw new RuntimeException('Dimensi favicon melebihi batas maksimal '.self::MAX_FAVICON_DIMENSION.'x'.self::MAX_FAVICON_DIMENSION.'px.');
        }
    }
}