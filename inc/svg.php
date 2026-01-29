<?php

/* `SVG Support — restricted to administrators
----------------------------------------------------------------------------------------------------*/

add_filter('upload_mimes', function ($mimes) {
    if (current_user_can('manage_options')) {
        $mimes['svg'] = 'image/svg+xml';
    }
    return $mimes;
});

add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {
    $filetype = wp_check_filetype($filename, $mimes);
    return [
        'ext'             => $filetype['ext'],
        'type'            => $filetype['type'],
        'proper_filename' => $data['proper_filename'],
    ];
}, 10, 4);

// Sanitize SVG on upload — strip scripts, event handlers, and unsafe tags
add_filter('wp_handle_upload_prefilter', function ($file) {
    if ($file['type'] !== 'image/svg+xml') {
        return $file;
    }

    $content = file_get_contents($file['tmp_name']);

    // Strip script tags, event handlers (onload, onclick, etc.), and unsafe elements
    $content = preg_replace('/<script[\s\S]*?<\/script>/i', '', $content);
    $content = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
    $content = preg_replace('/<(foreignObject|set|use[^r])[\s\S]*?<\/\1>/i', '', $content);
    $content = preg_replace('/xlink:href\s*=\s*["\'](?!#)[^"\']*["\']/i', '', $content);

    file_put_contents($file['tmp_name'], $content);

    return $file;
});

// Fix SVG display in admin media library
add_action('admin_head', function () {
    echo '<style>.attachment-266x266, .thumbnail img { width: 100% !important; height: auto !important; }</style>';
});