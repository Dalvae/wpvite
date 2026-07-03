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

if (!function_exists('starter_get_site_contact')) {
    function starter_get_site_contact(): array
    {
        $site_config = starter_get_site_config();
        $contact = $site_config['site']['contact'] ?? array();

        return is_array($contact) ? $contact : array();
    }
}

if (!function_exists('starter_get_site_email')) {
    function starter_get_site_email(): string
    {
        $contact = starter_get_site_contact();
        $configured = trim((string) ($contact['email'] ?? ''));
        $option = trim((string) get_option('starter_contact_email', ''));

        if ($configured !== '') {
            return sanitize_email($configured);
        }

        return $option !== '' ? sanitize_email($option) : sanitize_email((string) get_option('admin_email', ''));
    }
}

if (!function_exists('starter_get_site_phone')) {
    function starter_get_site_phone(): string
    {
        $contact = starter_get_site_contact();
        $configured = trim((string) ($contact['phone'] ?? ''));
        $option = trim((string) get_option('starter_contact_phone', ''));

        return $configured !== '' ? $configured : $option;
    }
}

if (!function_exists('starter_get_site_phone_href')) {
    function starter_get_site_phone_href(): string
    {
        $phone = starter_get_site_phone();
        $digits = preg_replace('/[^+0-9]/', '', $phone);

        return $digits ? 'tel:' . $digits : '';
    }
}
