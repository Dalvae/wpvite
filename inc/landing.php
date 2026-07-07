<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('starter_current_page_manifest')) {
    function starter_current_page_manifest(?int $post_id = null): array
    {
        $post_id = $post_id ?: (int) get_the_ID();
        $post = $post_id > 0 ? get_post($post_id) : null;
        if (!$post instanceof WP_Post) {
            return array();
        }

        $slugs = array($post->post_name);
        if (function_exists('starter_page_slug_from_post')) {
            $resolved = starter_page_slug_from_post($post_id);
            if ($resolved !== '') {
                array_unshift($slugs, $resolved);
            }
        }
        if ($post->post_parent > 0) {
            $parent = get_post($post->post_parent);
            if ($parent instanceof WP_Post) {
                $slugs[] = $parent->post_name . '/' . $post->post_name;
            }
        }

        foreach (array_values(array_unique(array_filter($slugs))) as $slug) {
            $manifest_path = get_template_directory() . '/manifests/pages/' . $slug . '.json';
            if (!file_exists($manifest_path)) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($manifest_path), true);
            if (is_array($manifest)) {
                return $manifest;
            }
        }

        return array();
    }
}

if (!function_exists('starter_is_landing_page')) {
    function starter_is_landing_page(?int $post_id = null): bool
    {
        $manifest = starter_current_page_manifest($post_id);
        return !empty($manifest['landing']);
    }
}

if (!function_exists('starter_is_performance_landing')) {
    function starter_is_performance_landing(): bool
    {
        return !is_admin() && starter_is_landing_page(get_queried_object_id());
    }
}

add_action('wp_head', function (): void {
    if (!starter_is_performance_landing()) {
        return;
    }

    $post_id = (int) get_queried_object_id();
    $manifest = starter_current_page_manifest($post_id);
    $meta_description = (string) ($manifest['seo']['meta_description'] ?? '');
    if ($meta_description !== '') {
        printf('<meta name="description" content="%s">' . "\n", esc_attr($meta_description));
    }

    $preload_image = (string) ($manifest['preload_image'] ?? '');
    $preload_image_id = (int) ($manifest['preload_image_id'] ?? 0);
    if ($preload_image === '' && $preload_image_id > 0) {
        $maybe_preload = wp_get_attachment_image_url($preload_image_id, 'full');
        $preload_image = is_string($maybe_preload) ? $maybe_preload : '';
    }
    if ($preload_image !== '') {
        printf('<link rel="preload" as="image" href="%s" media="(min-width: 768px)" fetchpriority="high">' . "\n", esc_url($preload_image));
    }

    $slug = function_exists('starter_page_slug_from_post') ? starter_page_slug_from_post($post_id) : '';
    if ($slug === '') {
        $post = get_post($post_id);
        $slug = $post instanceof WP_Post ? $post->post_name : '';
    }
    if ($slug !== '') {
        starter_output_critical_css($slug);
    }
}, 1);

add_action('wp_enqueue_scripts', function (): void {
    if (!starter_is_performance_landing()) {
        return;
    }

    wp_dequeue_style('wpvite-fonts');
    wp_deregister_style('wpvite-fonts');
    wp_dequeue_style('dashicons');
}, 100);

add_filter('script_loader_tag', function ($tag, $handle) {
    if (!starter_is_performance_landing() || str_contains($tag, ' defer')) {
        return $tag;
    }

    $defer_handles = apply_filters('starter_landing_defer_script_handles', array('jquery-core', 'jquery-migrate'));
    if (!in_array($handle, $defer_handles, true)) {
        return $tag;
    }

    return str_replace('<script ', '<script defer ', $tag);
}, 10, 2);

add_filter('style_loader_tag', function ($html, $handle, $href) {
    if (!starter_is_performance_landing()) {
        return $html;
    }

    if ($handle === 'theme' || str_contains((string) $href, '/dist/assets/theme-')) {
        $href = esc_url($href);
        return "<link rel='stylesheet' href='{$href}' media='(min-width: 768px)'>\n<link rel='preload' as='style' href='{$href}' media='(max-width: 767px)' onload=\"this.onload=null;this.rel='stylesheet'\">\n<noscript><link rel='stylesheet' href='{$href}'></noscript>\n";
    }

    $async_handles = apply_filters('starter_landing_async_style_handles', array());
    if (!in_array($handle, $async_handles, true)) {
        return $html;
    }

    $href = esc_url($href);
    return "<link rel='stylesheet' href='{$href}' media='print' onload=\"this.media='all'\">\n<noscript><link rel='stylesheet' href='{$href}'></noscript>\n";
}, 10, 3);
