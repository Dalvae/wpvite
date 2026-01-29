<?php

// Disable WP emoji scripts and styles
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');
add_filter('emoji_svg_url', '__return_false');

// Remove query strings from static resources
add_filter('script_loader_src', 'wpvite_remove_query_strings', 15, 1);
add_filter('style_loader_src', 'wpvite_remove_query_strings', 15, 1);
function wpvite_remove_query_strings($src) {
    if (strpos($src, '?ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}

// Disable XML-RPC
add_filter('xmlrpc_enabled', '__return_false');

// Remove WP version from head
remove_action('wp_head', 'wp_generator');

// Disable self-pingbacks
add_action('pre_ping', function (&$links) {
    $home = get_option('home');
    foreach ($links as $l => $link) {
        if (strpos($link, $home) === 0) {
            unset($links[$l]);
        }
    }
});

// Preconnect to Google Fonts (uncomment if using Google Fonts)
// add_filter('wp_resource_hints', function ($urls, $relation_type) {
//     if ($relation_type === 'dns-prefetch') {
//         $urls[] = 'https://fonts.googleapis.com';
//         $urls[] = 'https://fonts.gstatic.com';
//     }
//     return $urls;
// }, 10, 2);
