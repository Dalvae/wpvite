<?php
/**
 * Read-only WPML translation job listing.
 */

require_once __DIR__ . '/bootstrap.php';

global $wpdb;

$args = wr_wpml_cli_parse_args(array(
    'limit' => 20,
    'status' => null,
    'language' => null,
), isset($args) && is_array($args) ? $args : null);

$limit = max(1, min(200, (int) $args['limit']));
$status_filter = $args['status'] !== null && $args['status'] !== '' ? (string) $args['status'] : null;
$language_filter = $args['language'] !== null && $args['language'] !== '' ? (string) $args['language'] : null;

$jobs_table = wr_wpml_cli_table_name('icl_translate_job');
$status_table = wr_wpml_cli_table_name('icl_translation_status');
$translations_table = wr_wpml_cli_table_name('icl_translations');
$batches_table = wr_wpml_cli_table_name('icl_translation_batches');

$tables = array(
    'icl_translate_job' => wr_wpml_cli_table_exists($jobs_table),
    'icl_translation_status' => wr_wpml_cli_table_exists($status_table),
    'icl_translations' => wr_wpml_cli_table_exists($translations_table),
    'icl_translation_batches' => wr_wpml_cli_table_exists($batches_table),
);

if (!$tables['icl_translate_job'] || !$tables['icl_translation_status']) {
    wr_wpml_cli_json(array(
        'args' => array('limit' => $limit, 'status' => $status_filter, 'language' => $language_filter),
        'tables' => $tables,
        'summary' => array('by_status' => array(), 'batch_count' => $tables['icl_translation_batches'] ? wr_wpml_cli_table_count($batches_table) : null),
        'jobs' => array(),
        'message' => 'Required WPML job/status tables are missing; no jobs listed.',
    ));
    return;
}

$job_columns = wr_wpml_cli_table_columns($jobs_table);
$status_columns = wr_wpml_cli_table_columns($status_table);
$translation_columns = wr_wpml_cli_table_columns($translations_table);

$select = array(
    wr_wpml_cli_has_column($job_columns, 'job_id') ? 'j.job_id AS job_id' : 'NULL AS job_id',
    wr_wpml_cli_has_column($job_columns, 'rid') ? 'j.rid AS rid' : 'NULL AS rid',
    wr_wpml_cli_has_column($status_columns, 'translation_id') ? 's.translation_id AS translation_id' : 'NULL AS translation_id',
    wr_wpml_cli_has_column($status_columns, 'status') ? 's.status AS status' : 'NULL AS status',
    wr_wpml_cli_has_column($status_columns, 'needs_update') ? 's.needs_update AS needs_update' : 'NULL AS needs_update',
    wr_wpml_cli_has_column($job_columns, 'translator_id') ? 'j.translator_id AS translator_id' : 'NULL AS translator_id',
    wr_wpml_cli_has_column($status_columns, 'batch_id') ? 's.batch_id AS batch_id' : (wr_wpml_cli_has_column($job_columns, 'batch_id') ? 'j.batch_id AS batch_id' : 'NULL AS batch_id'),
    wr_wpml_cli_has_column($status_columns, 'timestamp') ? 's.timestamp AS timestamp' : (wr_wpml_cli_has_column($job_columns, 'timestamp') ? 'j.timestamp AS timestamp' : 'NULL AS timestamp'),
);

$join_translations = $tables['icl_translations'] && wr_wpml_cli_has_column($status_columns, 'translation_id') && wr_wpml_cli_has_column($translation_columns, 'translation_id');
if ($join_translations) {
    $select[] = wr_wpml_cli_has_column($translation_columns, 'source_language_code') ? 't.source_language_code AS source_language' : 'NULL AS source_language';
    $select[] = wr_wpml_cli_has_column($translation_columns, 'language_code') ? 't.language_code AS target_language' : 'NULL AS target_language';
    $select[] = wr_wpml_cli_has_column($translation_columns, 'element_id') ? 't.element_id AS element_id' : 'NULL AS element_id';
    $select[] = wr_wpml_cli_has_column($translation_columns, 'element_type') ? 't.element_type AS element_type' : 'NULL AS element_type';
} else {
    $select[] = 'NULL AS source_language';
    $select[] = 'NULL AS target_language';
    $select[] = 'NULL AS element_id';
    $select[] = 'NULL AS element_type';
}

$sql = 'SELECT ' . implode(', ', $select) . " FROM `{$jobs_table}` j INNER JOIN `{$status_table}` s ON j.rid = s.rid";
if ($join_translations) {
    $sql .= " LEFT JOIN `{$translations_table}` t ON s.translation_id = t.translation_id";
}

$where = array();
$params = array();
if ($status_filter !== null && wr_wpml_cli_has_column($status_columns, 'status')) {
    $where[] = 's.status = %s';
    $params[] = $status_filter;
}
if ($language_filter !== null && $join_translations) {
    $language_parts = array();
    if (wr_wpml_cli_has_column($translation_columns, 'language_code')) {
        $language_parts[] = 't.language_code = %s';
        $params[] = $language_filter;
    }
    if (wr_wpml_cli_has_column($translation_columns, 'source_language_code')) {
        $language_parts[] = 't.source_language_code = %s';
        $params[] = $language_filter;
    }
    if (!empty($language_parts)) {
        $where[] = '(' . implode(' OR ', $language_parts) . ')';
    }
}
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$order_parts = array();
if (wr_wpml_cli_has_column($status_columns, 'timestamp')) {
    $order_parts[] = 's.timestamp DESC';
}
if (wr_wpml_cli_has_column($job_columns, 'job_id')) {
    $order_parts[] = 'j.job_id DESC';
}
if (!empty($order_parts)) {
    $sql .= ' ORDER BY ' . implode(', ', $order_parts);
}
$sql .= ' LIMIT %d';
$params[] = $limit;

$jobs = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

$summary_sql = "SELECT s.status AS status, COUNT(*) AS count FROM `{$status_table}` s GROUP BY s.status ORDER BY count DESC";
$summary_rows = wr_wpml_cli_has_column($status_columns, 'status') ? $wpdb->get_results($summary_sql, ARRAY_A) : array();
$by_status = array();
foreach ((array) $summary_rows as $row) {
    $by_status[(string) $row['status']] = (int) $row['count'];
}

wr_wpml_cli_json(array(
    'args' => array('limit' => $limit, 'status' => $status_filter, 'language' => $language_filter),
    'tables' => $tables,
    'summary' => array(
        'by_status' => $by_status,
        'batch_count' => $tables['icl_translation_batches'] ? wr_wpml_cli_table_count($batches_table) : null,
    ),
    'jobs' => is_array($jobs) ? $jobs : array(),
));
