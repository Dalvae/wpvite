<?php

// Exit if accessed directly
if (! defined('ABSPATH'))
    exit;

use Kucrut\Vite;

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'wpvite-fonts',
        'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );

    Vite\enqueue_asset(
        __DIR__ . '/../dist',
        'src/theme.js',
        [
            'handle'    => 'theme',
            'in-footer' => true,
        ]
    );
});

add_action('wp_head', function (): void {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 1);
