<?php
/**
 * Shared helpers for read-only WPML WP-CLI diagnostics.
 *
 * Intended for use with: wp eval-file scripts/wpml-cli/<script>.php
 */

if (!defined('ABSPATH')) {
    exit('Run with WP-CLI: wp eval-file scripts/wpml-cli/<script>.php');
}

function wr_wpml_cli_json($data) {
    echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

function wr_wpml_cli_table_name($base) {
    global $wpdb;

    return $wpdb->prefix . $base;
}

function wr_wpml_cli_table_exists($table) {
    global $wpdb;

    return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
}

function wr_wpml_cli_table_count($table) {
    global $wpdb;

    if (!wr_wpml_cli_table_exists($table)) {
        return null;
    }

    return (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
}

function wr_wpml_cli_table_columns($table) {
    global $wpdb;

    if (!wr_wpml_cli_table_exists($table)) {
        return array();
    }

    $rows = $wpdb->get_results("DESCRIBE `{$table}`", ARRAY_A);

    return array_map(
        static function ($row) {
            return $row['Field'];
        },
        is_array($rows) ? $rows : array()
    );
}

function wr_wpml_cli_has_column($columns, $column) {
    return in_array($column, $columns, true);
}

function wr_wpml_cli_parse_args($defaults = array(), $raw_args = null) {
    $parsed = $defaults;
    if ($raw_args === null) {
        $raw_args = isset($GLOBALS['args']) && is_array($GLOBALS['args']) ? $GLOBALS['args'] : array();
        if (empty($raw_args) && isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
            $raw_args = $_SERVER['argv'];
        }
    }

    foreach ($raw_args as $arg) {
        if (strpos($arg, '--') === 0) {
            $arg = substr($arg, 2);
        }
        if (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) {
            $parsed[$matches[1]] = $matches[2];
        } elseif (preg_match('/^([^=]+)=(.*)$/', $arg, $matches)) {
            $parsed[$matches[1]] = $matches[2];
        }
    }

    return $parsed;
}

function wr_wpml_cli_constant_value($name) {
    return defined($name) ? constant($name) : null;
}
