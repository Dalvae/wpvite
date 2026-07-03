<?php
/**
 * Shared read-only helpers for Fluent Forms audit scripts.
 *
 * Intended for: wp eval-file scripts/fluentforms-cli/*.php
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "This script must be run through WordPress, e.g. wp eval-file.\n");
    exit(1);
}

function ffcli_json_out($data, $status = 0)
{
    echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if ($status !== 0) {
        exit($status);
    }
}

function ffcli_bool_arg($value)
{
    if (is_bool($value)) {
        return $value;
    }
    return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'y', 'on'), true);
}

function ffcli_get_args()
{
    $assoc = array();
    if (defined('WP_CLI') && WP_CLI && isset($GLOBALS['assoc_args']) && is_array($GLOBALS['assoc_args'])) {
        $assoc = $GLOBALS['assoc_args'];
    }

    if (!$assoc && isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
        foreach ($_SERVER['argv'] as $arg) {
            if (strpos($arg, '--') === 0) {
                $arg = substr($arg, 2);
            }
            if ($arg === '') {
                continue;
            }
            if (strpos($arg, '=') !== false) {
                list($key, $value) = explode('=', $arg, 2);
            } else {
                $key = $arg;
                $value = '1';
            }
            $assoc[$key] = $value;
        }
    }

    return $assoc;
}

function ffcli_table_name($suffix)
{
    global $wpdb;
    return $wpdb->prefix . $suffix;
}

function ffcli_show_like($like)
{
    global $wpdb;
    return $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $like));
}

function ffcli_table_exists($table)
{
    $matches = ffcli_show_like($table);
    return in_array($table, $matches, true);
}

function ffcli_tables_like_fluentform()
{
    global $wpdb;
    $like = $wpdb->esc_like($wpdb->prefix) . '%fluentform%';
    return ffcli_show_like($like);
}

function ffcli_columns($table)
{
    global $wpdb;
    if (!ffcli_table_exists($table)) {
        return array();
    }
    $rows = $wpdb->get_results('SHOW COLUMNS FROM `' . esc_sql($table) . '`', ARRAY_A);
    $columns = array();
    foreach ((array) $rows as $row) {
        if (isset($row['Field'])) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

function ffcli_has_column($columns, $column)
{
    return in_array($column, $columns, true);
}

function ffcli_count_table($table)
{
    global $wpdb;
    if (!ffcli_table_exists($table)) {
        return null;
    }
    return (int) $wpdb->get_var('SELECT COUNT(*) FROM `' . esc_sql($table) . '`');
}

function ffcli_active_plugin_versions()
{
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $active = (array) get_option('active_plugins', array());
    if (is_multisite()) {
        $active = array_merge($active, array_keys((array) get_site_option('active_sitewide_plugins', array())));
    }

    $plugins = get_plugins();
    $found = array();
    foreach ($plugins as $file => $data) {
        $needle = strtolower($file . ' ' . ($data['Name'] ?? ''));
        if (strpos($needle, 'fluentform') === false && strpos($needle, 'fluent forms') === false) {
            continue;
        }
        $found[] = array(
            'file' => $file,
            'name' => $data['Name'] ?? '',
            'version' => $data['Version'] ?? '',
            'active' => in_array($file, $active, true),
        );
    }
    return $found;
}

function ffcli_detect_readiness()
{
    $classes = array(
        'FluentForm\\App\\Modules\\Form\\Form',
        'FluentForm\\App\\Models\\Form',
        'FluentForm\\App\\Models\\Submission',
        'FluentFormPro\\App\\Modules\\Integrations\\IntegrationManager',
    );
    $functions = array('wpFluentForm', 'FluentForm', 'fluentFormApi');
    $hooks = array('fluentform_loaded', 'fluentform_before_insert_submission', 'fluentform_after_submission_inserted');

    $class_status = array();
    foreach ($classes as $class) {
        $class_status[$class] = class_exists($class);
    }

    $function_status = array();
    foreach ($functions as $function) {
        $function_status[$function] = function_exists($function);
    }

    return array(
        'classes' => $class_status,
        'functions' => $function_status,
        'hooks_have_listeners' => array_reduce($hooks, function ($carry, $hook) {
            $carry[$hook] = has_action($hook) ? true : false;
            return $carry;
        }, array()),
    );
}

function ffcli_decode_json($value)
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }
    $decoded = json_decode($value, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
}

function ffcli_compact_field_summary($fields)
{
    if (!is_array($fields)) {
        return array();
    }

    $flat = array();
    $walker = function ($node) use (&$walker, &$flat) {
        if (!is_array($node)) {
            return;
        }

        $attrs = isset($node['attributes']) && is_array($node['attributes']) ? $node['attributes'] : array();
        $settings = isset($node['settings']) && is_array($node['settings']) ? $node['settings'] : array();
        $type = $node['element'] ?? $node['type'] ?? $attrs['type'] ?? null;

        if ($type) {
            $options = $settings['advanced_options'] ?? $settings['options'] ?? $node['options'] ?? array();
            $flat[] = array(
                'type' => $type,
                'label' => $settings['label'] ?? $node['label'] ?? null,
                'name' => $attrs['name'] ?? $node['name'] ?? null,
                'required' => !empty($settings['validation_rules']['required']['value']) || !empty($settings['required']),
                'options_count' => is_array($options) ? count($options) : 0,
            );
        }

        foreach (array('fields', 'columns', 'settings') as $childKey) {
            if (isset($node[$childKey]) && is_array($node[$childKey])) {
                foreach ($node[$childKey] as $child) {
                    $walker($child);
                }
            }
        }
    };

    foreach ($fields as $field) {
        $walker($field);
    }

    return array_slice($flat, 0, 200);
}
