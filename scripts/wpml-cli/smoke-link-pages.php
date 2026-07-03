<?php
/**
 * Controlled WPML smoke test for language-linking draft pages.
 *
 * This intentionally does NOT create/export/import Translation Management jobs.
 * It creates two draft pages, links them via documented WPML hooks, verifies the
 * language details, and deletes only posts carrying the run-specific smoke marker.
 *
 * Usage:
 *   wp eval-file scripts/wpml-cli/smoke-link-pages.php
 *   wp eval-file scripts/wpml-cli/smoke-link-pages.php apply=1 confirm=OMC_SMOKE_WPML
 *   wp eval-file scripts/wpml-cli/smoke-link-pages.php cleanup=1 confirm=OMC_SMOKE_WPML
 */

require_once __DIR__ . '/bootstrap.php';

$args = wr_wpml_cli_parse_args(array(
    'apply' => '0',
    'cleanup' => '0',
    'confirm' => '',
));

$apply = in_array(strtolower((string) $args['apply']), array('1', 'true', 'yes', 'on'), true);
$cleanup = in_array(strtolower((string) $args['cleanup']), array('1', 'true', 'yes', 'on'), true);
$confirm = (string) $args['confirm'];
$marker_key = '_wr_wpml_smoke_test';
$marker_prefix = 'OMC_SMOKE_WPML_';

function wr_wpml_smoke_bool($value) {
    return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'on'), true);
}

function wr_wpml_smoke_find_marked_posts($marker_key, $run_id = '') {
    $query = array(
        'post_type' => 'page',
        'post_status' => 'any',
        'meta_key' => $marker_key,
        'fields' => 'ids',
        'posts_per_page' => -1,
    );
    if ($run_id !== '') {
        $query['meta_value'] = $run_id;
    }
    return array_map('intval', get_posts($query));
}

function wr_wpml_smoke_delete_posts($ids) {
    $deleted = array();
    foreach ($ids as $id) {
        $result = wp_delete_post((int) $id, true);
        $deleted[] = array('id' => (int) $id, 'deleted' => (bool) $result);
    }
    return $deleted;
}

$default_lang = has_filter('wpml_default_language') ? apply_filters('wpml_default_language', null) : null;
$languages = has_filter('wpml_active_languages') ? apply_filters('wpml_active_languages', null, array('skip_missing' => 0)) : array();
$target_lang = null;
if (is_array($languages)) {
    foreach ($languages as $code => $language) {
        if ((string) $code !== (string) $default_lang) {
            $target_lang = (string) $code;
            break;
        }
    }
}

$pre_existing = wr_wpml_smoke_find_marked_posts($marker_key);
$result = array(
    'mode' => array('apply' => $apply, 'cleanup' => $cleanup),
    'site' => array('home' => home_url('/'), 'theme' => get_stylesheet()),
    'wpml' => array(
        'default_language' => $default_lang,
        'target_language' => $target_lang,
        'has_set_language_hook' => has_action('wpml_set_element_language_details') !== false,
        'has_element_language_filter' => has_filter('wpml_element_language_details') !== false,
        'has_element_type_filter' => has_filter('wpml_element_type') !== false,
    ),
    'pre_existing_smoke_post_ids' => $pre_existing,
    'actions' => array(),
    'errors' => array(),
);

if ($cleanup) {
    if ($confirm !== 'OMC_SMOKE_WPML') {
        $result['errors'][] = 'cleanup requires confirm=OMC_SMOKE_WPML';
        wr_wpml_cli_json($result);
        return;
    }
    $result['actions'][] = array('cleanup_deleted' => wr_wpml_smoke_delete_posts($pre_existing));
    $result['remaining_smoke_post_ids'] = wr_wpml_smoke_find_marked_posts($marker_key);
    wr_wpml_cli_json($result);
    return;
}

if (!$apply) {
    $result['plan'] = array(
        'Create two draft pages with marker ' . $marker_prefix . '<run-id>',
        'Assign source language and target language with documented WPML hooks',
        'Verify shared trid/language details',
        'Delete only posts with the exact run marker',
        'Verify no smoke posts remain',
    );
    wr_wpml_cli_json($result);
    return;
}

if ($confirm !== 'OMC_SMOKE_WPML') {
    $result['errors'][] = 'apply requires confirm=OMC_SMOKE_WPML';
    wr_wpml_cli_json($result);
    return;
}
if (!$default_lang || !$target_lang) {
    $result['errors'][] = 'Need at least two active WPML languages for smoke test.';
    wr_wpml_cli_json($result);
    return;
}
if (has_action('wpml_set_element_language_details') === false || has_filter('wpml_element_language_details') === false) {
    $result['errors'][] = 'Required WPML language hooks are not available.';
    wr_wpml_cli_json($result);
    return;
}

$run_id = $marker_prefix . gmdate('Ymd_His') . '_' . wp_generate_password(6, false, false);
$source_id = 0;
$target_id = 0;

try {
    $source_id = wp_insert_post(array(
        'post_type' => 'page',
        'post_status' => 'draft',
        'post_title' => 'WR WPML Smoke Source ' . $run_id,
        'post_name' => strtolower(str_replace('_', '-', $run_id)) . '-source',
        'post_content' => 'Temporary WPML smoke source. Safe to delete.',
    ), true);
    if (is_wp_error($source_id)) {
        throw new RuntimeException($source_id->get_error_message());
    }

    $target_id = wp_insert_post(array(
        'post_type' => 'page',
        'post_status' => 'draft',
        'post_title' => 'WR WPML Smoke Target ' . $run_id,
        'post_name' => strtolower(str_replace('_', '-', $run_id)) . '-target',
        'post_content' => 'Temporary WPML smoke target. Safe to delete.',
    ), true);
    if (is_wp_error($target_id)) {
        throw new RuntimeException($target_id->get_error_message());
    }

    update_post_meta($source_id, $marker_key, $run_id);
    update_post_meta($target_id, $marker_key, $run_id);

    $element_type = has_filter('wpml_element_type') ? apply_filters('wpml_element_type', 'post_page') : 'post_page';

    do_action('wpml_set_element_language_details', array(
        'element_id' => $source_id,
        'element_type' => $element_type,
        'trid' => false,
        'language_code' => $default_lang,
        'source_language_code' => null,
    ));

    $source_details = apply_filters('wpml_element_language_details', null, array(
        'element_id' => $source_id,
        'element_type' => $element_type,
    ));
    if (!is_object($source_details) || empty($source_details->trid)) {
        throw new RuntimeException('Could not read source WPML language details/trid.');
    }

    do_action('wpml_set_element_language_details', array(
        'element_id' => $target_id,
        'element_type' => $element_type,
        'trid' => $source_details->trid,
        'language_code' => $target_lang,
        'source_language_code' => $default_lang,
    ));

    $target_details = apply_filters('wpml_element_language_details', null, array(
        'element_id' => $target_id,
        'element_type' => $element_type,
    ));

    $verified = is_object($target_details)
        && (string) $target_details->trid === (string) $source_details->trid
        && (string) $target_details->language_code === (string) $target_lang;

    $result['run_id'] = $run_id;
    $result['created'] = array('source_id' => (int) $source_id, 'target_id' => (int) $target_id);
    $result['language_details'] = array('source' => $source_details, 'target' => $target_details);
    $result['verified'] = $verified;

    $result['actions'][] = array('deleted' => wr_wpml_smoke_delete_posts(wr_wpml_smoke_find_marked_posts($marker_key, $run_id)));
    $result['remaining_for_run'] = wr_wpml_smoke_find_marked_posts($marker_key, $run_id);
    $result['remaining_any_smoke'] = wr_wpml_smoke_find_marked_posts($marker_key);
} catch (Throwable $e) {
    $result['errors'][] = $e->getMessage();
    $cleanup_ids = array_filter(array((int) $source_id, (int) $target_id));
    if (!empty($cleanup_ids)) {
        $result['actions'][] = array('error_cleanup_deleted' => wr_wpml_smoke_delete_posts($cleanup_ids));
    }
    $result['remaining_any_smoke'] = wr_wpml_smoke_find_marked_posts($marker_key);
}

wr_wpml_cli_json($result);
