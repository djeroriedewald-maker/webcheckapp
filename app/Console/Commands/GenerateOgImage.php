<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateOgImage extends Command
{
    protected $signature = 'app:generate-og-image';
    protected $description = 'Generate the static og-image.png (1200×630) for social sharing';

    public function handle(): int
    {
        $width  = 1200;
        $height = 630;

        $img = imagecreatetruecolor($width, $height);

        // Colors
        $bg      = imagecolorallocate($img, 15, 13, 42);    // #0f0d2a (dark indigo)
        $indigo  = imagecolorallocate($img, 99, 102, 241);   // #6366f1
        $purple  = imagecolorallocate($img, 139, 92, 246);   // #8b5cf6
        $white   = imagecolorallocate($img, 255, 255, 255);
        $gray    = imagecolorallocate($img, 156, 163, 175);  // #9ca3af
        $dimGray = imagecolorallocate($img, 75, 85, 99);     // #4b5563

        // Background
        imagefilledrectangle($img, 0, 0, $width, $height, $bg);

        // Gradient accent bar at top
        for ($x = 0; $x < $width; $x++) {
            $ratio = $x / $width;
            $r = (int) (99 + ($ratio * (139 - 99)));
            $g = (int) (102 + ($ratio * (92 - 102)));
            $b = (int) (241 + ($ratio * (246 - 241)));
            $color = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, $x, 0, $x, 4, $color);
        }

        // Subtle glow circle (center, very faint)
        imagealphablending($img, true);
        for ($r = 200; $r > 0; $r -= 4) {
            $alpha = (int) (127 - ($r / 200) * 5);
            $glow = imagecolorallocatealpha($img, 79, 70, 180, $alpha);
            imagefilledellipse($img, (int) ($width / 2), (int) ($height / 2) - 20, $r * 3, $r * 2, $glow);
        }

        // Use built-in font (no TTF dependency)
        // Brand name — large
        $brandFont = 5; // largest built-in font (9x15 px)
        $brand     = 'WebCheckApp';
        $brandW    = imagefontwidth($brandFont) * strlen($brand);
        imagestring($img, $brandFont, ($width - $brandW) / 2, 200, $brand, $white);

        // Shield icon (simple drawn shape)
        $cx = $width / 2;
        $cy = 160;
        $shieldPoints = [
            $cx, $cy - 40,       // top
            $cx + 30, $cy - 25,  // top-right
            $cx + 30, $cy + 5,   // mid-right
            $cx, $cy + 30,       // bottom
            $cx - 30, $cy + 5,   // mid-left
            $cx - 30, $cy - 25,  // top-left
        ];
        imagefilledpolygon($img, $shieldPoints, 6, $indigo);

        // Checkmark inside shield
        imageline($img, (int) $cx - 10, (int) $cy - 5, (int) $cx - 2, (int) $cy + 5, $white);
        imageline($img, (int) $cx - 2, (int) $cy + 5, (int) $cx + 12, (int) $cy - 12, $white);
        imageline($img, (int) $cx - 10, (int) $cy - 4, (int) $cx - 2, (int) $cy + 6, $white);
        imageline($img, (int) $cx - 2, (int) $cy + 6, (int) $cx + 12, (int) $cy - 11, $white);

        // Tagline
        $tagline  = 'Free Website Security Scanner';
        $tagFont  = 4; // medium built-in font
        $tagW     = imagefontwidth($tagFont) * strlen($tagline);
        imagestring($img, $tagFont, ($width - $tagW) / 2, 235, $tagline, $gray);

        // Features line
        $features = '19 Security Checks  |  Instant Results  |  Actionable Fixes';
        $featFont = 3;
        $featW    = imagefontwidth($featFont) * strlen($features);
        imagestring($img, $featFont, ($width - $featW) / 2, 270, $features, $dimGray);

        // URL at bottom
        $url     = 'webcheckapp.com';
        $urlFont = 4;
        $urlW    = imagefontwidth($urlFont) * strlen($url);
        imagestring($img, $urlFont, ($width - $urlW) / 2, $height - 60, $url, $indigo);

        // Bottom gradient bar
        for ($x = 0; $x < $width; $x++) {
            $ratio = $x / $width;
            $r = (int) (99 + ($ratio * (139 - 99)));
            $g = (int) (102 + ($ratio * (92 - 102)));
            $b = (int) (241 + ($ratio * (246 - 241)));
            $color = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, $x, $height - 4, $x, $height, $color);
        }

        $path = public_path('og-image.png');
        imagepng($img, $path, 6);
        imagedestroy($img);

        $this->info("og-image.png generated at {$path} ({$width}×{$height})");

        return self::SUCCESS;
    }
}
