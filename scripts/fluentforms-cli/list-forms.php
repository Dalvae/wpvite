<?php
/**
 * Read-only Fluent Forms form list audit.
 * Usage: wp eval-file scripts/fluentforms-cli/list-forms.php -- --limit=50 --include-fields=1
 */

require_once __DIR__ . '/bootstrap.php';

global $wpdb;

$args = ffcli_get_args();
$limit = isset($args['limit']) ? (int) $args['limit'] : 50;
$limit = max(1, min(200, $limit));
$include_fields = isset($args['include-fields']) ? ffcli_bool_arg($args['include-fields']) : false;
$form_id = isset($args['form-id']) ? absint($args['form-id']) : 0;

$forms_table = ffcli_table_name('fluentform_forms');
if (!ffcli_table_exists($forms_table)) {
    ffcli_json_out(array(
        'args' => array('limit' => $limit, 'include_fields' => $include_fields, 'form_id' => $form_id ?: null),
        'error' => 'Fluent Forms forms table was not found.',
        'table' => $forms_table,
        'forms' => array(),
    ));
}

$columns = ffcli_columns($forms_table);
$select_candidates = array('id', 'title', 'status', 'created_at', 'updated_at', 'form_fields', 'settings');
$select = array();
foreach ($select_candidates as $candidate) {
    if (ffcli_has_column($columns, $candidate)) {
        $select[] = '`' . esc_sql($candidate) . '`';
    }
}
if (!$select) {
    $select[] = '*';
}

$where = '';
$params = array();
if ($form_id && ffcli_has_column($columns, 'id')) {
    $where = ' WHERE `id` = %d';
    $params[] = $form_id;
}

$order = ffcli_has_column($columns, 'id') ? ' ORDER BY `id` ASC' : '';
$sql = 'SELECT ' . implode(', ', $select) . ' FROM `' . esc_sql($forms_table) . '`' . $where . $order . ' LIMIT %d';
$params[] = $limit;
$rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

$submissions_table = ffcli_table_name('fluentform_submissions');
$submission_counts = array();
if (ffcli_table_exists($submissions_table) && ffcli_has_column(ffcli_columns($submissions_table), 'form_id')) {
    $ids = array();
    foreach ((array) $rows as $row) {
        if (isset($row['id'])) {
            $ids[] = (int) $row['id'];
        }
    }
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $count_rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT `form_id`, COUNT(*) AS total FROM `' . esc_sql($submissions_table) . '` WHERE `form_id` IN (' . $placeholders . ') GROUP BY `form_id`',
                $ids
            ),
            ARRAY_A
        );
        foreach ((array) $count_rows as $count_row) {
            $submission_counts[(int) $count_row['form_id']] = (int) $count_row['total'];
        }
    }
}

$forms = array();
foreach ((array) $rows as $row) {
    $id = isset($row['id']) ? (int) $row['id'] : null;
    $item = array(
        'id' => $id,
        'title' => $row['title'] ?? null,
        'status' => $row['status'] ?? null,
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
        'submission_count' => $id !== null && isset($submission_counts[$id]) ? $submission_counts[$id] : 0,
    );

    if ($include_fields) {
        $decoded = array();
        if (isset($row['form_fields'])) {
            $decoded = ffcli_decode_json($row['form_fields']);
        }
        $fields = is_array($decoded) && isset($decoded['fields']) ? $decoded['fields'] : $decoded;
        $item['fields'] = ffcli_compact_field_summary($fields);
        if (isset($row['settings'])) {
            $settings = ffcli_decode_json($row['settings']);
            $item['settings_summary'] = is_array($settings) ? array(
                'has_confirmation' => isset($settings['confirmation']),
                'has_restrictions' => isset($settings['restrictions']) || isset($settings['limitNumberOfEntries']),
            ) : null;
        }
    }

    $forms[] = $item;
}

ffcli_json_out(array(
    'args' => array(
        'limit' => $limit,
        'include_fields' => $include_fields,
        'form_id' => $form_id ?: null,
    ),
    'schema' => array(
        'forms_table' => $forms_table,
        'forms_columns' => $columns,
        'submissions_table' => $submissions_table,
        'submissions_table_exists' => ffcli_table_exists($submissions_table),
    ),
    'forms' => $forms,
));
