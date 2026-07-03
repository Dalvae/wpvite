<?php
/**
 * Self-cleaning Fluent Forms smoke test.
 *
 * Dry-run by default. To apply:
 * wp eval-file scripts/fluentforms-cli/smoke-test.php apply=1 confirm=OMC_SMOKE_FLUENT
 */

require_once __DIR__ . '/bootstrap.php';

global $wpdb;

$marker = 'OMC_SMOKE_FLUENT_';
$confirm_token = 'OMC_SMOKE_FLUENT';
$args = ffcli_get_args();
$apply = isset($args['apply']) ? ffcli_bool_arg($args['apply']) : false;
$cleanup = isset($args['cleanup']) ? ffcli_bool_arg($args['cleanup']) : false;
$confirmed = isset($args['confirm']) && $args['confirm'] === $confirm_token;
$run_id = gmdate('Ymd_His') . '_' . strtolower(wp_generate_password(6, false, false));
$label = $marker . $run_id;

$result = array(
    'mode' => array(
        'apply' => $apply,
        'cleanup' => $cleanup,
        'confirmed' => $confirmed,
        'run_id' => $run_id,
        'marker' => $marker,
    ),
    'plan' => array(),
    'actions' => array(),
    'created' => array('forms' => array(), 'submissions' => array()),
    'deleted' => array(),
    'remaining' => array(),
    'errors' => array(),
);

function ffcli_smoke_table($suffix)
{
    return ffcli_table_name($suffix);
}

function ffcli_smoke_add_action(&$result, $action, $status, $details = array())
{
    $result['actions'][] = array_merge(array('action' => $action, 'status' => $status), $details);
}

function ffcli_smoke_insert_data($columns, $data)
{
    $filtered = array();
    foreach ($data as $key => $value) {
        if (ffcli_has_column($columns, $key)) {
            $filtered[$key] = $value;
        }
    }
    return $filtered;
}

function ffcli_smoke_find_marker_form_ids($needle)
{
    global $wpdb;
    $table = ffcli_smoke_table('fluentform_forms');
    if (!ffcli_table_exists($table)) {
        return array();
    }

    $columns = ffcli_columns($table);
    $like_parts = array();
    foreach (array('title', 'form_fields', 'settings') as $column) {
        if (ffcli_has_column($columns, $column)) {
            $like_parts[] = '`' . esc_sql($column) . '` LIKE %s';
        }
    }
    if (!$like_parts || !ffcli_has_column($columns, 'id')) {
        return array();
    }

    $like = '%' . $wpdb->esc_like($needle) . '%';
    $params = array_fill(0, count($like_parts), $like);
    $sql = 'SELECT `id` FROM `' . esc_sql($table) . '` WHERE ' . implode(' OR ', $like_parts);
    return array_map('intval', (array) $wpdb->get_col($wpdb->prepare($sql, $params)));
}

function ffcli_smoke_delete_by_form_ids($table, $form_ids, $form_id_columns, &$result)
{
    global $wpdb;
    if (!$form_ids || !ffcli_table_exists($table)) {
        return 0;
    }
    $columns = ffcli_columns($table);
    $deleted = 0;
    foreach ($form_id_columns as $column) {
        if (!ffcli_has_column($columns, $column)) {
            continue;
        }
        $placeholders = implode(',', array_fill(0, count($form_ids), '%d'));
        $sql = 'DELETE FROM `' . esc_sql($table) . '` WHERE `' . esc_sql($column) . '` IN (' . $placeholders . ')';
        $ok = $wpdb->query($wpdb->prepare($sql, $form_ids));
        if ($ok === false) {
            $result['errors'][] = 'Delete failed for ' . $table . '.' . $column . ': ' . $wpdb->last_error;
        } else {
            $deleted += (int) $ok;
        }
    }
    return $deleted;
}

function ffcli_smoke_delete_marker_rows($table, $needle, $text_columns, &$result)
{
    global $wpdb;
    if (!ffcli_table_exists($table)) {
        return 0;
    }
    $columns = ffcli_columns($table);
    $parts = array();
    foreach ($text_columns as $column) {
        if (ffcli_has_column($columns, $column)) {
            $parts[] = '`' . esc_sql($column) . '` LIKE %s';
        }
    }
    if (!$parts) {
        return 0;
    }
    $like = '%' . $wpdb->esc_like($needle) . '%';
    $params = array_fill(0, count($parts), $like);
    $ok = $wpdb->query($wpdb->prepare('DELETE FROM `' . esc_sql($table) . '` WHERE ' . implode(' OR ', $parts), $params));
    if ($ok === false) {
        $result['errors'][] = 'Marker delete failed for ' . $table . ': ' . $wpdb->last_error;
        return 0;
    }
    return (int) $ok;
}

function ffcli_smoke_cleanup($needle, &$result)
{
    global $wpdb;
    $form_ids = ffcli_smoke_find_marker_form_ids($needle);
    $result['actions'][] = array('action' => 'find_marker_forms', 'status' => 'done', 'form_ids' => $form_ids);
    $deleted = array();

    $submissions = ffcli_smoke_table('fluentform_submissions');
    $submission_ids = array();
    $submission_columns = ffcli_columns($submissions);
    if ($form_ids && ffcli_table_exists($submissions) && ffcli_has_column($submission_columns, 'form_id') && ffcli_has_column($submission_columns, 'id')) {
        $placeholders = implode(',', array_fill(0, count($form_ids), '%d'));
        $submission_ids = array_map('intval', (array) $wpdb->get_col($wpdb->prepare('SELECT `id` FROM `' . esc_sql($submissions) . '` WHERE `form_id` IN (' . $placeholders . ')', $form_ids)));
    }

    if ($submission_ids) {
        foreach (array('fluentform_submission_meta', 'fluentform_entry_details') as $suffix) {
            $table = ffcli_smoke_table($suffix);
            $columns = ffcli_columns($table);
            foreach (array('submission_id', 'entry_id') as $column) {
                if (!ffcli_has_column($columns, $column)) {
                    continue;
                }
                $placeholders = implode(',', array_fill(0, count($submission_ids), '%d'));
                $count = $wpdb->query($wpdb->prepare('DELETE FROM `' . esc_sql($table) . '` WHERE `' . esc_sql($column) . '` IN (' . $placeholders . ')', $submission_ids));
                $deleted[$suffix . '.' . $column] = (int) $count;
            }
        }
    }

    $deleted['fluentform_submissions'] = ffcli_smoke_delete_by_form_ids($submissions, $form_ids, array('form_id'), $result);
    $deleted['fluentform_form_meta'] = ffcli_smoke_delete_by_form_ids(ffcli_smoke_table('fluentform_form_meta'), $form_ids, array('form_id'), $result);
    $deleted['fluentform_logs'] = ffcli_smoke_delete_by_form_ids(ffcli_smoke_table('fluentform_logs'), $form_ids, array('form_id'), $result);
    $deleted['fluentform_logs_marker'] = ffcli_smoke_delete_marker_rows(ffcli_smoke_table('fluentform_logs'), $needle, array('title', 'message', 'description', 'data'), $result);

    if ($form_ids && ffcli_table_exists(ffcli_smoke_table('fluentform_forms'))) {
        $placeholders = implode(',', array_fill(0, count($form_ids), '%d'));
        $count = $wpdb->query($wpdb->prepare('DELETE FROM `' . esc_sql(ffcli_smoke_table('fluentform_forms')) . '` WHERE `id` IN (' . $placeholders . ')', $form_ids));
        $deleted['fluentform_forms'] = (int) $count;
    }

    $result['deleted'][] = $deleted;
    return $deleted;
}

function ffcli_smoke_remaining($needle)
{
    $remaining = array('forms' => ffcli_smoke_find_marker_form_ids($needle));
    foreach (array('fluentform_form_meta', 'fluentform_submissions', 'fluentform_submission_meta', 'fluentform_entry_details', 'fluentform_logs') as $suffix) {
        $table = ffcli_smoke_table($suffix);
        if (!ffcli_table_exists($table)) {
            $remaining[$suffix] = null;
            continue;
        }
        $remaining[$suffix] = ffcli_smoke_delete_marker_rows_count_only($table, $needle, array('value', 'meta_value', 'response', 'message', 'description', 'data'));
    }
    return $remaining;
}

function ffcli_smoke_delete_marker_rows_count_only($table, $needle, $text_columns)
{
    global $wpdb;
    $columns = ffcli_columns($table);
    $parts = array();
    foreach ($text_columns as $column) {
        if (ffcli_has_column($columns, $column)) {
            $parts[] = '`' . esc_sql($column) . '` LIKE %s';
        }
    }
    if (!$parts) {
        return 0;
    }
    $like = '%' . $wpdb->esc_like($needle) . '%';
    $params = array_fill(0, count($parts), $like);
    return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM `' . esc_sql($table) . '` WHERE ' . implode(' OR ', $parts), $params));
}

$result['plan'] = array(
    'create temporary form titled ' . $label,
    'read the form back from ' . ffcli_smoke_table('fluentform_forms'),
    'create a temporary submission only if submission table schema has form_id and response columns',
    'delete temporary submission/meta/detail rows linked to the smoke form',
    'delete temporary form/meta/log rows matching only marker/run_id',
    'verify no marker rows remain for this run_id',
);

if ($apply && !$confirmed) {
    $result['errors'][] = 'apply=1 requires confirm=OMC_SMOKE_FLUENT';
    ffcli_json_out($result, 1);
}

if (!$apply) {
    ffcli_smoke_add_action($result, 'dry_run', 'planned', array('note' => 'No database writes performed. Pass apply=1 confirm=OMC_SMOKE_FLUENT to execute.'));
    if ($cleanup) {
        ffcli_smoke_add_action($result, 'cleanup_dry_run', 'planned', array('target_marker' => $marker));
        $result['remaining'] = ffcli_smoke_remaining($marker);
    }
    ffcli_json_out($result);
    return;
}

if ($cleanup) {
    ffcli_smoke_cleanup($marker, $result);
    $result['remaining'] = ffcli_smoke_remaining($marker);
    ffcli_json_out($result, $result['errors'] ? 1 : 0);
    return;
}

$forms_table = ffcli_smoke_table('fluentform_forms');
if (!ffcli_table_exists($forms_table)) {
    $result['errors'][] = 'Cannot run smoke test: forms table missing: ' . $forms_table;
    ffcli_json_out($result, 1);
}

$form_columns = ffcli_columns($forms_table);
$fields = array(
    'fields' => array(
        array('element' => 'input_text', 'attributes' => array('name' => 'omc_smoke_name'), 'settings' => array('label' => 'Name', 'validation_rules' => array('required' => array('value' => true)))),
        array('element' => 'input_email', 'attributes' => array('name' => 'omc_smoke_email'), 'settings' => array('label' => 'Email', 'validation_rules' => array('required' => array('value' => true)))),
        array('element' => 'textarea', 'attributes' => array('name' => 'omc_smoke_message'), 'settings' => array('label' => 'Message')),
    ),
    'marker' => $label,
);
$now = current_time('mysql', true);
$form_data = ffcli_smoke_insert_data($form_columns, array(
    'title' => $label,
    'form_fields' => wp_json_encode($fields),
    'status' => 'published',
    'type' => 'form',
    'has_payment' => 0,
    'created_by' => get_current_user_id(),
    'created_at' => $now,
    'updated_at' => $now,
    'settings' => wp_json_encode(array('marker' => $label, 'confirmation' => array('message' => 'OMC smoke test'))),
));

if (!ffcli_has_column($form_columns, 'id') || !isset($form_data['title']) || !isset($form_data['form_fields'])) {
    $result['errors'][] = 'Cannot safely create temp form: required id/title/form_fields columns not detected.';
    ffcli_json_out($result, 1);
}

$inserted = $wpdb->insert($forms_table, $form_data);
if (!$inserted) {
    $result['errors'][] = 'Form insert failed: ' . $wpdb->last_error;
    ffcli_json_out($result, 1);
}
$form_id = (int) $wpdb->insert_id;
$result['created']['forms'][] = array('id' => $form_id, 'title' => $label);
ffcli_smoke_add_action($result, 'create_form', 'done', array('form_id' => $form_id));

$read_back = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . esc_sql($forms_table) . '` WHERE `id` = %d', $form_id), ARRAY_A);
ffcli_smoke_add_action($result, 'read_back_form', $read_back ? 'done' : 'failed', array('found' => (bool) $read_back));

$submissions_table = ffcli_smoke_table('fluentform_submissions');
$submission_id = 0;
if (ffcli_table_exists($submissions_table)) {
    $submission_columns = ffcli_columns($submissions_table);
    if (ffcli_has_column($submission_columns, 'form_id') && ffcli_has_column($submission_columns, 'response')) {
        $submission_data = ffcli_smoke_insert_data($submission_columns, array(
            'form_id' => $form_id,
            'serial_number' => 1,
            'response' => wp_json_encode(array('omc_smoke_name' => 'OMC Smoke', 'omc_smoke_email' => 'omc-smoke@example.invalid', 'omc_smoke_message' => $label)),
            'source_url' => home_url('/?omc_smoke=' . rawurlencode($run_id)),
            'user_id' => get_current_user_id(),
            'status' => 'unread',
            'is_favourite' => 0,
            'browser' => 'OMC smoke test',
            'ip' => '127.0.0.1',
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $ok = $wpdb->insert($submissions_table, $submission_data);
        if ($ok) {
            $submission_id = (int) $wpdb->insert_id;
            $result['created']['submissions'][] = array('id' => $submission_id, 'form_id' => $form_id);
            ffcli_smoke_add_action($result, 'create_submission', 'done', array('submission_id' => $submission_id));
        } else {
            ffcli_smoke_add_action($result, 'create_submission', 'skipped', array('reason' => 'Insert failed: ' . $wpdb->last_error));
        }
    } else {
        ffcli_smoke_add_action($result, 'create_submission', 'skipped', array('reason' => 'Submission table lacks form_id/response columns.'));
    }
} else {
    ffcli_smoke_add_action($result, 'create_submission', 'skipped', array('reason' => 'Submission table missing.'));
}

ffcli_smoke_cleanup($label, $result);
$result['remaining'] = ffcli_smoke_remaining($label);

ffcli_json_out($result, $result['errors'] ? 1 : 0);
