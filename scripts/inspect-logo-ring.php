<?php

$src = imagecreatefromjpeg(__DIR__ . '/../assets/images/logo.jpg');
$w = imagesx($src);
$h = imagesy($src);
$cx = (int) ($w / 2);
$cy = (int) ($h / 2);

function isGold($rgb): bool
{
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;

    return $r > 120 && $g > 90 && $b < 120 && ($r + $g) > ($b + 100);
}

$maxDist = 0;
for ($angle = 0; $angle < 360; $angle += 2) {
    $rad = deg2rad($angle);
    $lastGold = 0;

    for ($dist = (int) ($w * 0.35); $dist < (int) ($w * 0.5); $dist++) {
        $x = (int) round($cx + cos($rad) * $dist);
        $y = (int) round($cy + sin($rad) * $dist);

        if ($x < 0 || $y < 0 || $x >= $w || $y >= $h) {
            break;
        }

        if (isGold(imagecolorat($src, $x, $y))) {
            $lastGold = $dist;
        }
    }

    if ($lastGold > $maxDist) {
        $maxDist = $lastGold;
    }
}

echo 'ring_outer_radius=' . $maxDist . PHP_EOL;

$distances = [];
for ($angle = 0; $angle < 360; $angle += 15) {
    $rad = deg2rad($angle);
    $lastGold = 0;

    for ($dist = (int) ($w * 0.35); $dist < (int) ($w * 0.5); $dist++) {
        $x = (int) round($cx + cos($rad) * $dist);
        $y = (int) round($cy + sin($rad) * $dist);

        if ($x < 0 || $y < 0 || $x >= $w || $y >= $h) {
            break;
        }

        if (isGold(imagecolorat($src, $x, $y))) {
            $lastGold = $dist;
        }
    }

    $distances[] = $lastGold;
}

echo 'avg_ring_radius=' . round(array_sum($distances) / count($distances), 1) . PHP_EOL;
echo 'min=' . min($distances) . ' max=' . max($distances) . PHP_EOL;
