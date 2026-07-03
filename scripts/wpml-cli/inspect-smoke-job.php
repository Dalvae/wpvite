<?php
global $wpdb, $sitepress, $iclTranslationManagement;

$post_id = (int) $wpdb->get_var(
    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wr_wpml_job_smoke' ORDER BY post_id DESC LIMIT 1"
);
$post = $post_id ? get_post($post_id) : null;
$default_lang = has_filter('wpml_default_language') ? apply_filters('wpml_default_language', null) : null;
$langs = has_filter('wpml_active_languages') ? apply_filters('wpml_active_languages', null, array('skip_missing' => 0)) : array();
$out_langs = array();
if (is_array($langs)) {
    foreach ($langs as $code => $language) {
        $out_langs[] = $code;
    }
}
$etype = has_filter('wpml_element_type') ? apply_filters('wpml_element_type', 'post_page') : 'post_page';

$out = array(
    'post_id' => $post_id,
    'post_status' => $post ? $post->post_status : null,
    'post_title' => $post ? $post->post_title : null,
    'default_lang' => $default_lang,
    'languages' => $out_langs,
    'element_type' => $etype,
    'trid' => $post_id && $sitepress ? $sitepress->get_element_trid($post_id, $etype) : null,
    'details' => $post_id ? apply_filters('wpml_element_language_details', null, array('element_id' => $post_id, 'element_type' => $etype)) : null,
    'existing_translation_es' => ($post_id && isset($iclTranslationManagement)) ? $iclTranslationManagement->get_element_translation($post_id, 'es', $etype) : null,
    'recent_jobs' => $wpdb->get_results(
        "SELECT j.job_id,j.rid,j.translator_id,j.translated,s.translation_id,s.status,s.needs_update,s.batch_id,s.timestamp,t.element_id,t.element_type,t.language_code,t.source_language_code " .
        "FROM {$wpdb->prefix}icl_translate_job j " .
        "JOIN {$wpdb->prefix}icl_translation_status s ON s.rid=j.rid " .
        "JOIN {$wpdb->prefix}icl_translations t ON t.translation_id=s.translation_id " .
        "ORDER BY j.job_id DESC LIMIT 5",
        ARRAY_A
    ),
);

echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
