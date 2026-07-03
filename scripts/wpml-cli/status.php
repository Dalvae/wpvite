<?php
/**
 * Read-only WPML status diagnostics.
 */

require_once __DIR__ . '/bootstrap.php';

$wpml_tables = array(
    'icl_translations',
    'icl_translate_job',
    'icl_translation_status',
    'icl_translation_batches',
    'icl_strings',
    'icl_string_translations',
    'icl_string_packages',
);

$tables = array();
foreach ($wpml_tables as $base) {
    $table = wr_wpml_cli_table_name($base);
    $exists = wr_wpml_cli_table_exists($table);
    $tables[$base] = array(
        'table' => $table,
        'exists' => $exists,
        'count' => $exists ? wr_wpml_cli_table_count($table) : null,
    );
}

if (!function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$active_plugins = array();
$plugins = function_exists('get_plugins') ? get_plugins() : array();
foreach ((array) get_option('active_plugins', array()) as $plugin_file) {
    $plugin = isset($plugins[$plugin_file]) ? $plugins[$plugin_file] : array();
    $name = isset($plugin['Name']) ? $plugin['Name'] : $plugin_file;
    $text = strtolower($plugin_file . ' ' . $name);

    if (preg_match('/wpml|sitepress|string-translation|translation-management|woocommerce-multilingual/', $text)) {
        $active_plugins[] = array(
            'file' => $plugin_file,
            'name' => $name,
            'version' => isset($plugin['Version']) ? $plugin['Version'] : null,
        );
    }
}

$active_languages = null;
if (has_filter('wpml_active_languages')) {
    $active_languages = apply_filters('wpml_active_languages', null, array('skip_missing' => 0));
} elseif (function_exists('icl_get_languages')) {
    $active_languages = icl_get_languages('skip_missing=0');
}

$default_language = has_filter('wpml_default_language') ? apply_filters('wpml_default_language', null) : null;
$current_language = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : null;

$status = array(
    'site' => array(
        'home' => home_url('/'),
        'siteurl' => site_url('/'),
        'theme' => array(
            'stylesheet' => get_stylesheet(),
            'template' => get_template(),
        ),
        'wp_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
    ),
    'wpml_readiness' => array(
        'wpml_related_plugins_active' => !empty($active_plugins),
        'active_wpml_plugins' => $active_plugins,
        'actions' => array(
            'wpml_loaded' => has_action('wpml_loaded') !== false,
            'wpml_st_loaded' => has_action('wpml_st_loaded') !== false,
            'wpml_tm_loaded' => has_action('wpml_tm_loaded') !== false,
        ),
        'safe_api_hooks' => array(
            'wpml_register_single_string' => array(
                'has_action' => has_action('wpml_register_single_string') !== false,
                'has_filter' => has_filter('wpml_register_single_string') !== false,
                'function_exists' => function_exists('wpml_register_single_string'),
            ),
            'wpml_translate_single_string' => array(
                'has_action' => has_action('wpml_translate_single_string') !== false,
                'has_filter' => has_filter('wpml_translate_single_string') !== false,
                'function_exists' => function_exists('wpml_translate_single_string'),
            ),
            'wpml_set_element_language_details' => array(
                'has_action' => has_action('wpml_set_element_language_details') !== false,
                'has_filter' => has_filter('wpml_set_element_language_details') !== false,
                'function_exists' => function_exists('wpml_set_element_language_details'),
            ),
        ),
        'functions' => array(
            'icl_get_languages' => function_exists('icl_get_languages'),
        ),
        'constants' => array(
            'ICL_SITEPRESS_VERSION' => wr_wpml_cli_constant_value('ICL_SITEPRESS_VERSION'),
            'WPML_ST_VERSION' => wr_wpml_cli_constant_value('WPML_ST_VERSION'),
            'WPML_TM_VERSION' => wr_wpml_cli_constant_value('WPML_TM_VERSION'),
        ),
    ),
    'languages' => array(
        'active' => $active_languages,
        'default' => $default_language,
        'current' => $current_language,
    ),
    'tables' => $tables,
    'xliff_config' => array(
        'WPML_SAVE_XLIFF_PATH_defined' => defined('WPML_SAVE_XLIFF_PATH'),
        'WPML_SAVE_XLIFF_PATH' => wr_wpml_cli_constant_value('WPML_SAVE_XLIFF_PATH'),
        'WPML_EXPORT_ALL_TO_XLIFF_LIMIT_defined' => defined('WPML_EXPORT_ALL_TO_XLIFF_LIMIT'),
        'WPML_EXPORT_ALL_TO_XLIFF_LIMIT' => wr_wpml_cli_constant_value('WPML_EXPORT_ALL_TO_XLIFF_LIMIT'),
    ),
);

wr_wpml_cli_json($status);
