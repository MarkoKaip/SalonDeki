<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/manifest+json; charset=utf-8');

$manifest = [
    'id' => url('index.php'),
    'name' => salonAppName(),
    'short_name' => 'Deki Salon',
    'description' => 'Zakažite termin u frizerskom salonu Deki.',
    'start_url' => url('index.php'),
    'scope' => url('/') === '' ? '/' : url('/'),
    'display' => 'standalone',
    'orientation' => 'portrait',
    'background_color' => '#0a0a0a',
    'theme_color' => '#0a0a0a',
    'lang' => 'sr',
    'icons' => [
        [
            'src' => asset('icon-192.png') . '?v=2',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => asset('icon-512.png') . '?v=2',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => asset('icon-512-maskable.png') . '?v=2',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'maskable',
        ],
    ],
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
