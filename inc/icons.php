<?php

if (!function_exists('starter_render_local_svg')) {
    function starter_render_local_svg(string $icon_path, string $cache_key, string $class_prefix, string $name, array $args = array()): string
    {
        if (!file_exists($icon_path)) {
            return '';
        }

        static $svg_cache = array();
        if (!isset($svg_cache[$cache_key])) {
            $svg = file_get_contents($icon_path);
            if (!is_string($svg) || trim($svg) === '') {
                return '';
            }

            $svg_cache[$cache_key] = $svg;
        }

        $svg = $svg_cache[$cache_key];
        $decorative = (bool) $args['decorative'];
        $label = trim((string) $args['label']);

        if (!$decorative && $label === '') {
            $label = ucwords(str_replace('-', ' ', $name));
        }

        $classes = trim($class_prefix . ' ' . $class_prefix . '-' . $name . ' ' . (string) $args['class']);
        $attrs = array(
            'class' => $classes,
            'width' => (string) $args['size'],
            'height' => (string) $args['size'],
            'focusable' => 'false',
        );

        if ($decorative) {
            $attrs['aria-hidden'] = 'true';
        } else {
            $attrs['role'] = 'img';
            $attrs['aria-label'] = $label;
        }

        $attr_chunks = array();
        foreach ($attrs as $key => $value) {
            if ($value === '') {
                continue;
            }

            $attr_chunks[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        $attr_string = implode(' ', $attr_chunks);

        return preg_replace('/<svg\b/', '<svg ' . $attr_string, $svg, 1) ?: '';
    }
}

if (!function_exists('starter_resolve_icon_name')) {
    function starter_resolve_icon_name(string $name): string
    {
        $aliases = array(
            'mail' => 'envelope-simple',
            'external-link' => 'arrow-up-right',
            'globe' => 'globe-hemisphere-west',
            'menu' => 'list',
        );

        $normalized = sanitize_key($name);

        return $aliases[$normalized] ?? $normalized;
    }
}

if (!function_exists('starter_icon')) {
    function starter_icon(string $name, array $args = array()): string
    {
        $defaults = array(
            'weight' => 'regular',
            'class' => '',
            'size' => '1em',
            'decorative' => true,
            'label' => '',
        );

        $args = wp_parse_args($args, $defaults);
        $weight = sanitize_key((string) $args['weight']);
        $resolved_name = starter_resolve_icon_name($name);

        if (!preg_match('/^[a-z0-9-]+$/', $resolved_name) || !preg_match('/^[a-z0-9-]+$/', $weight)) {
            return '';
        }

        $icon_path = get_theme_file_path("assets/icons/phosphor/{$weight}/{$resolved_name}.svg");

        return starter_render_local_svg($icon_path, $weight . '/' . $resolved_name, 'ph-icon', $resolved_name, $args);
    }
}

if (!function_exists('starter_the_icon')) {
    function starter_the_icon(string $name, array $args = array()): void
    {
        echo starter_icon($name, $args); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
