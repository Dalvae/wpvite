<?php

if (!function_exists('starter_load_theme_json_config')) {
    function starter_load_theme_json_config(string $relative_path): array
    {
        static $cache = array();

        if (isset($cache[$relative_path])) {
            return $cache[$relative_path];
        }

        $path = get_theme_file_path($relative_path);
        if (!file_exists($path)) {
            $cache[$relative_path] = array();
            return $cache[$relative_path];
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            $cache[$relative_path] = array();
            return $cache[$relative_path];
        }

        $decoded = json_decode($raw, true);
        $cache[$relative_path] = is_array($decoded) ? $decoded : array();

        return $cache[$relative_path];
    }
}

if (!function_exists('starter_get_brand_presets')) {
    function starter_get_brand_presets(): array
    {
        return starter_load_theme_json_config('config/brand-presets.json');
    }
}

if (!function_exists('starter_get_site_config')) {
    function starter_get_site_config(): array
    {
        return starter_load_theme_json_config('config/site.config.json');
    }
}

if (!function_exists('starter_get_site_brand_preset')) {
    function starter_get_site_brand_preset(): string
    {
        $site_config = starter_get_site_config();
        $preset = sanitize_key((string) ($site_config['brand']['preset'] ?? 'editorial-signal'));
        $presets = starter_get_brand_presets();

        return array_key_exists($preset, $presets) ? $preset : 'editorial-signal';
    }
}

if (!function_exists('starter_get_site_name')) {
    function starter_get_site_name(): string
    {
        $site_config = starter_get_site_config();
        $configured = trim((string) ($site_config['site']['name'] ?? ''));

        return $configured !== '' ? $configured : (string) get_bloginfo('name');
    }
}

if (!function_exists('starter_get_site_tagline')) {
    function starter_get_site_tagline(): string
    {
        $site_config = starter_get_site_config();
        $configured = trim((string) ($site_config['site']['tagline'] ?? ''));

        return $configured !== '' ? $configured : (string) get_bloginfo('description');
    }
}
