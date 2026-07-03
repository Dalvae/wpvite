<?php
/**
 * Read-only Fluent Forms status audit.
 * Usage: wp eval-file scripts/fluentforms-cli/status.php
 */

require_once __DIR__ . '/bootstrap.php';

global $wpdb, $wp_version;

$expected = array(
    'fluentform_forms',
    'fluentform_form_meta',
    'fluentform_submissions',
    'fluentform_submission_meta',
    'fluentform_logs',
    'fluentform_entry_details',
);

$tables = array();
foreach ($expected as $suffix) {
    $table = ffcli_table_name($suffix);
    $exists = ffcli_table_exists($table);
    $tables[$suffix] = array(
        'table' => $table,
        'exists' => $exists,
        'count' => $exists ? ffcli_count_table($table) : null,
    );
}

$submissions_table = ffcli_table_name('fluentform_submissions');
$latest_submission = null;
if (ffcli_table_exists($submissions_table)) {
    $columns = ffcli_columns($submissions_table);
    $timestamp_column = null;
    foreach (array('created_at', 'updated_at', 'created', 'date_created') as $candidate) {
        if (ffcli_has_column($columns, $candidate)) {
            $timestamp_column = $candidate;
            break;
        }
    }
    if ($timestamp_column) {
        $latest_submission = $wpdb->get_var('SELECT MAX(`' . esc_sql($timestamp_column) . '`) FROM `' . esc_sql($submissions_table) . '`');
    }
}

ffcli_json_out(array(
    'site' => array(
        'home' => home_url('/'),
        'siteurl' => site_url('/'),
        'theme_stylesheet' => get_stylesheet(),
        'theme_template' => get_template(),
        'wp_version' => $wp_version,
        'php_version' => PHP_VERSION,
        'db_prefix' => $wpdb->prefix,
    ),
    'fluent_forms_plugins' => ffcli_active_plugin_versions(),
    'readiness' => ffcli_detect_readiness(),
    'tables' => $tables,
    'detected_fluentform_tables' => ffcli_tables_like_fluentform(),
    'submissions' => array(
        'count' => $tables['fluentform_submissions']['count'],
        'latest_timestamp' => $latest_submission,
    ),
));
