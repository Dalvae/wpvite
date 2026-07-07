<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('starter_get_critical_css')) {
    function starter_get_critical_css(string $name): string
    {
        $name = sanitize_file_name($name);
        $path = get_template_directory() . '/dist/critical/' . $name . '.css';
        if (!file_exists($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }
}

if (!function_exists('starter_output_critical_css')) {
    function starter_output_critical_css(string $name): void
    {
        $css = starter_get_critical_css($name);
        if ($css === '') {
            return;
        }

        echo '<style id="starter-critical-css-' . esc_attr($name) . '">' . $css . '</style>' . "\n";
    }
}
