<?php

declare(strict_types=1);

$source = __DIR__ . '/../assets/images/logo.jpg';
$output = __DIR__ . '/../assets/images/logo-circle.png';

if (!extension_loaded('gd')) {
    fwrite(STDERR, "GD extension is not available.\n");
    exit(1);
}

if (!is_file($source)) {
    fwrite(STDERR, "Source logo not found.\n");
    exit(1);
}

function isGoldPixel(int $rgb): bool
{
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;

    return $r > 120 && $g > 90 && $b < 120 && ($r + $g) > ($b + 100);
}

function detectRingRadius($src, int $centerX, int $centerY, int $maxScan): int
{
    $samples = [];

    for ($angle = 0; $angle < 360; $angle += 10) {
        $rad = deg2rad($angle);
        $lastGold = 0;

        for ($dist = (int) ($maxScan * 0.35); $dist < $maxScan; $dist++) {
            $x = (int) round($centerX + cos($rad) * $dist);
            $y = (int) round($centerY + sin($rad) * $dist);

            if ($x < 0 || $y < 0 || $x >= imagesx($src) || $y >= imagesy($src)) {
                break;
            }

            if (isGoldPixel(imagecolorat($src, $x, $y))) {
                $lastGold = $dist;
            }
        }

        if ($lastGold > 0) {
            $samples[] = $lastGold;
        }
    }

    if ($samples === []) {
        return (int) round($maxScan * 0.48);
    }

    sort($samples);

    return (int) round($samples[(int) floor(count($samples) * 0.35)]);
}

$src = imagecreatefromjpeg($source);

if ($src === false) {
    fwrite(STDERR, "Could not read source logo.\n");
    exit(1);
}

$width = imagesx($src);
$height = imagesy($src);
$centerX = (int) round($width / 2);
$centerY = (int) round($height / 2);
$maxScan = (int) (min($width, $height) / 2);
$radius = detectRingRadius($src, $centerX, $centerY, $maxScan);
$outputSize = $radius * 2;

$outputImage = imagecreatetruecolor($outputSize, $outputSize);
imagesavealpha($outputImage, true);

$transparent = imagecolorallocatealpha($outputImage, 0, 0, 0, 127);
imagefill($outputImage, 0, 0, $transparent);
imagealphablending($outputImage, true);

for ($y = 0; $y < $outputSize; $y++) {
    for ($x = 0; $x < $outputSize; $x++) {
        $dx = $x - $radius + 0.5;
        $dy = $y - $radius + 0.5;

        if (hypot($dx, $dy) <= $radius) {
            $sourceX = (int) round($centerX - $radius + $x);
            $sourceY = (int) round($centerY - $radius + $y);
            imagesetpixel($outputImage, $x, $y, imagecolorat($src, $sourceX, $sourceY));
        }
    }
}

imagepng($outputImage, $output, 9);
imagedestroy($src);
imagedestroy($outputImage);

echo "Created {$output} (radius={$radius}, size={$outputSize})\n";
