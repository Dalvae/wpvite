<?php
/**
 * Template Name: Section Stack
 *
 * Single rendering path — all pages use section-stack composition.
 * All fields read via carbon_get_post_meta().
 *
 * Pages WITH a field map: per-field reconstruction.
 * Pages WITHOUT a field map: JSON blob fallback.
 */

get_header();

$post_id    = (int) get_the_ID();
$use_carbon = function_exists('carbon_get_post_meta');
$sections   = array();

// 1. Try per-field reconstruction (pages with field maps)
if (function_exists('starter_page_slug_from_post') && function_exists('starter_reconstruct_sections')) {
    $page_slug = starter_page_slug_from_post($post_id);
    if ($page_slug !== '') {
        $sections = starter_reconstruct_sections($post_id, $page_slug);
    }
}

// 2. Fallback to JSON blob
if (empty($sections)) {
    $raw = $use_carbon
        ? carbon_get_post_meta($post_id, 'starter_page_sections')
        : get_post_meta($post_id, 'starter_page_sections', true);

    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $sections = $decoded;
        }
    } elseif (is_array($raw)) {
        $sections = $raw;
    }
}

get_template_part(
    'template-parts/page-families/section-stack',
    null,
    array('sections' => $sections)
);

get_footer();
