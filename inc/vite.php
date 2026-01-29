<?php

// Exit if accessed directly
if (! defined('ABSPATH'))
    exit;

use Kucrut\Vite;

add_action('wp_enqueue_scripts', function (): void {
    Vite\enqueue_asset(
        __DIR__ . '/../dist',
        'src/theme.js',
        [
            'handle'    => 'theme',
            'in-footer' => true,
        ]
    );
});
