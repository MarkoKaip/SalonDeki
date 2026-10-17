<?php

declare(strict_types=1);

$source = __DIR__ . '/../assets/logo.png';
$outputDir = __DIR__ . '/../assets';
$rootDir = __DIR__ . '/..';

if (!extension_loaded('gd') || !is_file($source)) {
    fwrite(STDERR, "Missing GD extension or source logo.\n");
    exit(1);
}

function createAppIcon(string $source, string $output, int $size, float $paddingRatio): void
{
    $logo = imagecreatefrompng($source);

    if ($logo === false) {
        throw new RuntimeException('Could not read logo.png');
    }

    $canvas = imagecreatetruecolor($size, $size);
    $black = imagecolorallocate($canvas, 10, 10, 10);
    imagefill($canvas, 0, 0, $black);

    $padding = (int) round($size * $paddingRatio);
    $target = $size - ($padding * 2);
    $logoWidth = imagesx($logo);
    $logoHeight = imagesy($logo);

    imagecopyresampled(
        $canvas,
        $logo,
        $padding,
        $padding,
        0,
        0,
        $target,
        $target,
        $logoWidth,
        $logoHeight
    );

    imagejpeg($canvas, preg_replace('/\.png$/', '.jpg', $output), 92);
    imagepng($canvas, $output, 9);
    imagedestroy($logo);
    imagedestroy($canvas);
}

$icons = [
    'apple-touch-icon.png' => [180, 0.05],
    'icon-192.png' => [192, 0.05],
    'icon-512.png' => [512, 0.05],
    'icon-512-maskable.png' => [512, 0.16],
];

foreach ($icons as $filename => [$size, $paddingRatio]) {
    $path = $outputDir . '/' . $filename;
    createAppIcon($source, $path, $size, $paddingRatio);
    echo "Created {$filename}\n";
}

$rootIcons = [
    'apple-touch-icon.png' => [180, 0.05],
    'apple-touch-icon-precomposed.png' => [180, 0.05],
];

foreach ($rootIcons as $filename => [$size, $paddingRatio]) {
    $path = $rootDir . '/' . $filename;
    createAppIcon($source, $path, $size, $paddingRatio);
    echo "Created root {$filename}\n";
}

foreach (['apple-touch-icon.jpg', 'icon-192.jpg', 'icon-512.jpg'] as $jpgName) {
    $pngName = str_replace('.jpg', '.png', $jpgName);
    $from = $outputDir . '/' . $pngName;
    $to = $outputDir . '/' . $jpgName;

    if (is_file($from)) {
        echo "Created {$jpgName}\n";
    }
}

echo "Done\n";
