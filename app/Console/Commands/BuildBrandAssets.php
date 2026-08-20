<?php

namespace App\Console\Commands;

use GdImage;
use Illuminate\Console\Command;

/**
 * Regenerates the whole KASAROSE brand asset set from the designer's source art.
 *
 * Source art lives in `resources/brand/` — deliberately outside the web root, so
 * the working files (including the .psd) are never served. Outputs land in
 * `public/images/brand/` and `public/images/`.
 *
 * Always regenerate the full set rather than one size in isolation: the favicons,
 * lockups and OG card are derived from the same two source files and drift apart
 * if edited individually.
 */
class BuildBrandAssets extends Command
{
    protected $signature = 'brand:build';

    protected $description = 'Regenerate the KASAROSE logo, lockup, OG card and favicon set from resources/brand/';

    /** Brand red, sampled from the KASAROSE mark. */
    private const BRAND_RED = [0xEF, 0x41, 0x36];

    /** Deepest brand blue — the favicon and OG card field. */
    private const INK_DEEP = [0x02, 0x1E, 0x33];

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('The GD extension is required to build brand assets.');

            return self::FAILURE;
        }

        $source = resource_path('brand');
        $brandOut = public_path('images/brand');
        $imageOut = public_path('images');

        foreach (['kasa.png', 'kasarose.png', 'kasarose-2.png'] as $file) {
            if (! is_file("{$source}/{$file}")) {
                $this->error("Missing source art: resources/brand/{$file}");

                return self::FAILURE;
            }
        }

        // libpng warns about the sRGB profile the source files carry; harmless here.
        $mark = $this->trimAlpha($this->loadPng("{$source}/kasa.png"));
        $lockup = $this->trimAlpha($this->loadPng("{$source}/kasarose-2.png"));
        $stacked = $this->trimAlpha($this->loadPng("{$source}/kasarose.png"));

        $this->save($this->resizeToWidth($mark, 512), "{$brandOut}/logo-mark.png");
        $this->save($this->reverseForDark($this->resizeToWidth($mark, 512)), "{$brandOut}/logo-mark-on-dark.png");
        $this->save($this->resizeToWidth($lockup, 1200), "{$brandOut}/logo-lockup.png");
        $this->save($this->reverseForDark($this->resizeToWidth($lockup, 1200)), "{$brandOut}/logo-lockup-on-dark.png");
        $this->save($this->resizeToWidth($stacked, 800), "{$brandOut}/logo-stacked.png");
        $this->save($this->reverseForDark($this->resizeToWidth($stacked, 800)), "{$brandOut}/logo-stacked-on-dark.png");

        $this->save($this->openGraphCard($lockup), "{$brandOut}/og-image.png");

        $markOnDark = $this->reverseForDark($mark);
        $icons = [
            16 => "{$imageOut}/favicon-16x16.png",
            32 => "{$imageOut}/favicon-32x32.png",
            180 => "{$imageOut}/apple-touch-icon.png",
            192 => "{$imageOut}/android-chrome-192x192.png",
            512 => "{$imageOut}/android-chrome-512x512.png",
        ];

        foreach ($icons as $size => $path) {
            // Small sizes need a tighter inset or the mark reads as a smudge.
            $this->save($this->icon($markOnDark, $size, $size <= 32 ? 0.82 : 0.66), $path);
        }

        $this->writeIco([16 => $icons[16], 32 => $icons[32]], public_path('favicon.ico'));

        $this->newLine();
        $this->info('Brand assets rebuilt. Run `npm run build` if any token or CSS change accompanied this.');

        return self::SUCCESS;
    }

    private function loadPng(string $path): GdImage
    {
        $image = @imagecreatefrompng($path);

        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    private function blank(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocatealpha($image, 0, 0, 0, 127));

        return $image;
    }

    /** Crop fully transparent margins so every derived asset is optically tight. */
    private function trimAlpha(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $left = $width;
        $top = $height;
        $right = -1;
        $bottom = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ((((imagecolorat($image, $x, $y) >> 24) & 0x7F)) < 120) {
                    $left = min($left, $x);
                    $top = min($top, $y);
                    $right = max($right, $x);
                    $bottom = max($bottom, $y);
                }
            }
        }

        if ($right < 0) {
            return $image;
        }

        $trimmed = $this->blank($right - $left + 1, $bottom - $top + 1);
        imagecopy($trimmed, $image, 0, 0, $left, $top, $right - $left + 1, $bottom - $top + 1);

        return $trimmed;
    }

    private function resizeToWidth(GdImage $image, int $width): GdImage
    {
        $height = (int) round(imagesy($image) * ($width / imagesx($image)));
        $resized = $this->blank($width, $height);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $resized;
    }

    /**
     * Reversed ("on dark") variant: the blue mark and the black wordmark both go
     * white, while the red wedge is kept so the logo still reads as KASAROSE.
     */
    private function reverseForDark(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $reversed = $this->blank($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($image, $x, $y);
                $alpha = ($color >> 24) & 0x7F;

                if ($alpha >= 127) {
                    continue;
                }

                $red = ($color >> 16) & 0xFF;
                $green = ($color >> 8) & 0xFF;
                $blue = $color & 0xFF;

                $isBrandRed = $red > 110 && $red > $green * 1.6 && $red > $blue * 1.6;
                [$r, $g, $b] = $isBrandRed ? self::BRAND_RED : [0xFF, 0xFF, 0xFF];

                imagesetpixel($reversed, $x, $y, imagecolorallocatealpha($reversed, $r, $g, $b, $alpha));
            }
        }

        return $reversed;
    }

    /** 1200x630 social card: reversed lockup on the deep ink field, red foot rule. */
    private function openGraphCard(GdImage $lockup): GdImage
    {
        $card = imagecreatetruecolor(1200, 630);
        imagealphablending($card, true);
        imagesavealpha($card, false);
        imagefilledrectangle($card, 0, 0, 1200, 630, imagecolorallocate($card, ...self::INK_DEEP));
        imagefilledrectangle($card, 0, 606, 1200, 630, imagecolorallocate($card, ...self::BRAND_RED));

        $art = $this->reverseForDark($this->resizeToWidth($lockup, 760));
        imagecopy($card, $art, (int) ((1200 - 760) / 2), (int) ((606 - imagesy($art)) / 2), 0, 0, 760, imagesy($art));

        return $card;
    }

    /** Square app icon: the reversed mark, padded, on a solid deep-ink field. */
    private function icon(GdImage $mark, int $size, float $inset): GdImage
    {
        $icon = imagecreatetruecolor($size, $size);
        imagealphablending($icon, true);
        imagesavealpha($icon, true);
        imagefilledrectangle($icon, 0, 0, $size, $size, imagecolorallocate($icon, ...self::INK_DEEP));

        $scale = min(($size * $inset) / imagesx($mark), ($size * $inset) / imagesy($mark));
        $width = (int) round(imagesx($mark) * $scale);
        $height = (int) round(imagesy($mark) * $scale);

        imagecopyresampled(
            $icon,
            $mark,
            (int) (($size - $width) / 2),
            (int) (($size - $height) / 2),
            0,
            0,
            $width,
            $height,
            imagesx($mark),
            imagesy($mark),
        );

        return $icon;
    }

    private function save(GdImage $image, string $path): void
    {
        imagepng($image, $path, 9);

        $this->line(sprintf(
            '  <fg=gray>%s</> <fg=green>%dx%d</>',
            str_replace(base_path().DIRECTORY_SEPARATOR, '', $path),
            imagesx($image),
            imagesy($image),
        ));
    }

    /**
     * Minimal PNG-payload .ico (the Vista+ format, supported by every current
     * browser). GD has no .ico encoder, so the container is written by hand.
     *
     * @param  array<int, string>  $pngPaths  size => path
     */
    private function writeIco(array $pngPaths, string $path): void
    {
        $directory = '';
        $payload = '';
        $offset = 6 + (count($pngPaths) * 16);

        foreach ($pngPaths as $size => $png) {
            $bytes = file_get_contents($png);
            $dimension = $size >= 256 ? 0 : $size;

            $directory .= pack('CCCCvvVV', $dimension, $dimension, 0, 0, 1, 32, strlen($bytes), $offset);
            $offset += strlen($bytes);
            $payload .= $bytes;
        }

        file_put_contents($path, pack('vvv', 0, 1, count($pngPaths)).$directory.$payload);

        $this->line('  <fg=gray>'.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).'</>');
    }
}
