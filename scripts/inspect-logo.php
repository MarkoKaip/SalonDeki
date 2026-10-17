<?php

$source = __DIR__ . '/../assets/images/logo.jpg';
$info = getimagesize($source);
echo 'size=' . $info[0] . 'x' . $info[1] . PHP_EOL;

$src = imagecreatefromjpeg($source);
$w = imagesx($src);
$h = imagesy($src);

$goldPoints = 0;
$sumX = 0;
$sumY = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($src, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        if ($r > 120 && $g > 90 && $b < 120 && ($r + $g) > ($b + 100)) {
            $sumX += $x;
            $sumY += $y;
            $goldPoints++;
        }
    }
}

echo 'gold_points=' . $goldPoints . PHP_EOL;
echo 'centroid=' . round($sumX / max(1, $goldPoints), 1) . ',' . round($sumY / max(1, $goldPoints), 1) . PHP_EOL;
echo 'geom_center=' . ($w / 2) . ',' . ($h / 2) . PHP_EOL;
