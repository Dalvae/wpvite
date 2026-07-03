<?php
/**
 * Destructive WPML Translation Management smoke test for the current OMC smoke page.
 *
 * This intentionally exercises TM job tables using WPML's loaded internals plus
 * explicit cleanup of only rows created by this run. It does not operate on real
 * content: the source page must carry `_wr_wpml_job_smoke` meta.
 *
 * Usage:
 *   wp eval-file scripts/wpml-cli/smoke-tm-job-direct.php apply=1 confirm=OMC_SMOKE_WPML_JOB
 */

require_once __DIR__ . '/bootstrap.php';

global $wpdb, $sitepress, $iclTranslationManagement;

$args = wr_wpml_cli_parse_args(array('apply' => '0', 'confirm' => '', 'cleanup_source' => '1'));
$apply = in_array(strtolower((string) $args['apply']), array('1', 'true', 'yes', 'on'), true);
$confirm = (string) $args['confirm'];
$cleanup_source = in_array(strtolower((string) $args['cleanup_source']), array('1', 'true', 'yes', 'on'), true);

$out = array(
    'mode' => array('apply' => $apply, 'cleanup_source' => $cleanup_source),
    'created' => array(),
    'steps' => array(),
    'cleanup' => array(),
    'errors' => array(),
);

function wr_tm_step(&$out, $name, $data = array()) {
    $out['steps'][] = array_merge(array('step' => $name), $data);
}

function wr_tm_delete_where($table, $where, &$out, $label) {
    global $wpdb;
    $deleted = $wpdb->delete($table, $where);
    $out['cleanup'][$label] = ($deleted === false) ? array('error' => $wpdb->last_error) : (int) $deleted;
}

if (!$apply || $confirm !== 'OMC_SMOKE_WPML_JOB') {
    $out['plan'] = array(
        'Find latest page marked with _wr_wpml_job_smoke',
        'Create isolated ES icl_translations row if missing',
        'Create icl_translation_status row through WPML update_translation_status()',
        'Create TM job through TranslationManagement::add_translation_job()',
        'Mark job done through TranslationManagement::mark_job_done()',
        'Delete only created job/translate/status/translation rows and smoke source page',
    );
    if ($apply) {
        $out['errors'][] = 'apply=1 requires confirm=OMC_SMOKE_WPML_JOB';
    }
    wr_wpml_cli_json($out, empty($out['errors']) ? 0 : 1);
    return;
}

$post_id = (int) $wpdb->get_var(
    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wr_wpml_job_smoke' ORDER BY post_id DESC LIMIT 1"
);
$post = $post_id ? get_post($post_id) : null;
if (!$post || $post->post_type !== 'page') {
    $out['errors'][] = 'No smoke page found.';
    wr_wpml_cli_json($out, 1);
    return;
}
if (strpos((string) $post->post_title, 'OMC_SMOKE_WPML_JOB_') === false) {
    $out['errors'][] = 'Refusing to run: source page title is not smoke-marked.';
    wr_wpml_cli_json($out, 1);
    return;
}
if (!isset($sitepress) || !isset($iclTranslationManagement)) {
    $out['errors'][] = 'WPML Translation Management globals are not loaded.';
    wr_wpml_cli_json($out, 1);
    return;
}

$source_lang = apply_filters('wpml_default_language', null) ?: 'en';
$target_lang = 'es';
$element_type = apply_filters('wpml_element_type', 'post_page');
$trid = $sitepress->get_element_trid($post_id, $element_type);
if (!$trid) {
    $out['errors'][] = 'Source page has no WPML trid.';
    wr_wpml_cli_json($out, 1);
    return;
}

$translation_id = null;
$rid = null;
$job_id = null;
$batch_id = null;

try {
    wr_tm_step($out, 'source_found', array('post_id' => $post_id, 'title' => $post->post_title, 'trid' => $trid));

    $existing_translation_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code=%s AND element_type=%s LIMIT 1",
        $trid,
        $target_lang,
        $element_type
    ));
    if ($existing_translation_id) {
        $out['errors'][] = 'Target translation row already exists; refusing to touch existing translation_id=' . $existing_translation_id;
        throw new RuntimeException('existing target translation row');
    }

    $wpdb->insert($wpdb->prefix . 'icl_translations', array(
        'element_type' => $element_type,
        'element_id' => null,
        'trid' => $trid,
        'language_code' => $target_lang,
        'source_language_code' => $source_lang,
    ));
    if (!$wpdb->insert_id) {
        throw new RuntimeException('Failed to insert icl_translations: ' . $wpdb->last_error);
    }
    $translation_id = (int) $wpdb->insert_id;
    $out['created']['translation_id'] = $translation_id;
    wr_tm_step($out, 'created_target_translation_row', array('translation_id' => $translation_id));

    $package = $iclTranslationManagement->create_translation_package($post);
    if (!$package || !is_array($package)) {
        throw new RuntimeException('Could not create translation package.');
    }
    wr_tm_step($out, 'created_translation_package', array('keys' => array_keys($package)));

    $batch_name = 'OMC_SMOKE_WPML_JOB_DIRECT_' . gmdate('Ymd_His');
    $wpdb->insert($wpdb->prefix . 'icl_translation_batches', array(
        'batch_name' => $batch_name,
        'tp_id' => 0,
        'ts_url' => '',
        'last_update' => current_time('mysql'),
    ));
    $batch_id = (int) $wpdb->insert_id;
    $out['created']['batch_id'] = $batch_id;

    list($rid) = $iclTranslationManagement->update_translation_status(array(
        'translation_id' => $translation_id,
        'status' => defined('ICL_TM_WAITING_FOR_TRANSLATOR') ? ICL_TM_WAITING_FOR_TRANSLATOR : 1,
        'translator_id' => 1,
        'needs_update' => 0,
        'md5' => md5(wp_json_encode($package)),
        'translation_service' => 'local',
        'batch_id' => $batch_id,
        'translation_package' => maybe_serialize($package),
        'timestamp' => current_time('mysql'),
    ));
    $rid = (int) $rid;
    if (!$rid) {
        throw new RuntimeException('Could not create translation status rid.');
    }
    $out['created']['rid'] = $rid;
    wr_tm_step($out, 'created_translation_status', array('rid' => $rid));

    $job_result = $iclTranslationManagement->add_translation_job(
        $rid,
        1,
        $package,
        array('batch_name' => $batch_name, 'batch_id' => $batch_id),
        'local'
    );
    $job_id = is_array($job_result) && isset($job_result['job_id']) ? (int) $job_result['job_id'] : (int) $job_result;
    if (!$job_id) {
        $latest_job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d ORDER BY job_id DESC LIMIT 1",
            $rid
        ), ARRAY_A);
        $job_id = $latest_job ? (int) $latest_job['job_id'] : 0;
    }
    if (!$job_id) {
        throw new RuntimeException('Could not create translation job; result=' . wp_json_encode($job_result));
    }
    $out['created']['job_id'] = $job_id;
    wr_tm_step($out, 'created_translation_job', array('job_id' => $job_id, 'raw_result' => $job_result));

    $iclTranslationManagement->mark_job_done($job_id);
    $done = $wpdb->get_row($wpdb->prepare(
        "SELECT job_id, rid, translated FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d",
        $job_id
    ), ARRAY_A);
    wr_tm_step($out, 'marked_job_done', array('job' => $done));

    // Cleanup only rows created by this script/run.
    wr_tm_delete_where($wpdb->prefix . 'icl_translate', array('job_id' => $job_id), $out, 'icl_translate');
    wr_tm_delete_where($wpdb->prefix . 'icl_translate_job', array('job_id' => $job_id), $out, 'icl_translate_job');
    wr_tm_delete_where($wpdb->prefix . 'icl_translation_status', array('rid' => $rid), $out, 'icl_translation_status');
    wr_tm_delete_where($wpdb->prefix . 'icl_translations', array('translation_id' => $translation_id), $out, 'icl_translations');
    if ($batch_id) {
        wr_tm_delete_where($wpdb->prefix . 'icl_translation_batches', array('id' => $batch_id), $out, 'icl_translation_batches');
    }
    if ($cleanup_source) {
        $deleted_post = wp_delete_post($post_id, true);
        $out['cleanup']['source_post'] = (bool) $deleted_post;
    }

    $out['remaining'] = array(
        'job_rows' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id)),
        'translate_rows' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}icl_translate WHERE job_id=%d", $job_id)),
        'status_rows' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}icl_translation_status WHERE rid=%d", $rid)),
        'translation_rows' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d", $translation_id)),
        'source_post_exists' => (bool) get_post($post_id),
    );
} catch (Throwable $e) {
    $out['errors'][] = $e->getMessage();
    if ($job_id) {
        wr_tm_delete_where($wpdb->prefix . 'icl_translate', array('job_id' => $job_id), $out, 'error_icl_translate');
        wr_tm_delete_where($wpdb->prefix . 'icl_translate_job', array('job_id' => $job_id), $out, 'error_icl_translate_job');
    }
    if ($rid) {
        wr_tm_delete_where($wpdb->prefix . 'icl_translation_status', array('rid' => $rid), $out, 'error_icl_translation_status');
    }
    if ($translation_id) {
        wr_tm_delete_where($wpdb->prefix . 'icl_translations', array('translation_id' => $translation_id), $out, 'error_icl_translations');
    }
    if ($batch_id) {
        wr_tm_delete_where($wpdb->prefix . 'icl_translation_batches', array('id' => $batch_id), $out, 'error_icl_translation_batches');
    }
}

wr_wpml_cli_json($out, empty($out['errors']) ? 0 : 1);
