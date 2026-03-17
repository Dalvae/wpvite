<?php
/**
 * Carbon Fields — per-page section field definitions.
 *
 * Reads field maps from config/section-field-maps.json (single source of truth
 * shared with Python seeder and WPML generator).
 *
 * Flow:
 *   Manifest JSON → seed-content-rest.py (flatten) → per-field CF meta
 *   Per-field CF meta → starter_reconstruct_sections() → starter_render_sections()
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

/* ══════════════════════════════════════════════════════════════════════════
 * Load field maps from shared JSON config
 * ══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('starter_page_field_maps')) {
    /**
     * Load per-page section field maps from config/section-field-maps.json.
     *
     * @return array<string, array>  Keyed by page slug.
     */
    function starter_page_field_maps(): array
    {
        static $maps = null;
        if ($maps !== null) {
            return $maps;
        }

        $config_path = get_template_directory() . '/config/section-field-maps.json';
        if (!file_exists($config_path)) {
            $maps = array();
            return $maps;
        }

        $raw = file_get_contents($config_path);
        if ($raw === false) {
            $maps = array();
            return $maps;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['pages'])) {
            $maps = array();
            return $maps;
        }

        $maps = (array) $data['pages'];
        return $maps;
    }
}

/* ══════════════════════════════════════════════════════════════════════════
 * Meta Key Helpers
 * ══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('starter_all_section_meta_keys')) {
    /**
     * @return array<string, string>  meta_key => 'translate'|'copy'
     */
    function starter_all_section_meta_keys(): array
    {
        $keys = array();
        foreach (starter_page_field_maps() as $sections) {
            foreach ($sections as $section_def) {
                $prefix = $section_def['prefix'] ?? '';
                $fields = $section_def['fields'] ?? array();
                foreach ($fields as $field_name => $field_def) {
                    $meta_key = $prefix . '_' . $field_name;
                    $wpml = is_array($field_def) ? ($field_def['wpml'] ?? 'translate') : 'translate';
                    $keys[$meta_key] = $wpml;
                }
            }
        }
        return $keys;
    }
}

if (!function_exists('starter_field_storage_type')) {
    function starter_field_storage_type($field_def): string
    {
        if (is_array($field_def)) {
            return $field_def['storage'] ?? 'text';
        }
        return (string) $field_def;
    }
}

/* ══════════════════════════════════════════════════════════════════════════
 * Reconstruction — per-field Carbon Fields meta → sections array
 * ══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('starter_reconstruct_sections')) {
    /**
     * Read per-field CF meta and reconstruct the sections array.
     */
    function starter_reconstruct_sections(int $post_id, string $slug): array
    {
        $maps = starter_page_field_maps();
        if (!isset($maps[$slug])) {
            return array();
        }

        $use_carbon = function_exists('carbon_get_post_meta');
        $sections = array();

        foreach ($maps[$slug] as $section_def) {
            $section = array('type' => $section_def['type']);

            foreach (($section_def['overrides'] ?? array()) as $ok => $ov) {
                $section[$ok] = $ov;
            }

            $has_content = false;
            $fields = $section_def['fields'] ?? array();

            foreach ($fields as $field_name => $field_def) {
                $meta_key = $section_def['prefix'] . '_' . $field_name;
                $raw = $use_carbon
                    ? carbon_get_post_meta($post_id, $meta_key)
                    : get_post_meta($post_id, $meta_key, true);

                if ($raw === null || $raw === '' || $raw === false) {
                    continue;
                }

                $storage = starter_field_storage_type($field_def);
                if ($storage === 'json') {
                    if (is_string($raw)) {
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded)) {
                            $section[$field_name] = $decoded;
                            $has_content = true;
                        }
                    } elseif (is_array($raw)) {
                        $section[$field_name] = $raw;
                        $has_content = true;
                    }
                } else {
                    $section[$field_name] = (string) $raw;
                    $has_content = true;
                }
            }

            if ($has_content) {
                $sections[] = $section;
            }
        }

        return $sections;
    }
}

if (!function_exists('starter_page_slug_from_post')) {
    function starter_page_slug_from_post(int $post_id): string
    {
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }

        $slug = $post->post_name;
        $maps = starter_page_field_maps();

        if (isset($maps[$slug])) {
            return $slug;
        }

        if ($post->post_parent > 0) {
            $parent = get_post($post->post_parent);
            if ($parent) {
                $composite = $parent->post_name . '/' . $slug;
                if (isset($maps[$composite])) {
                    return $composite;
                }
            }
        }

        return '';
    }
}

/* ══════════════════════════════════════════════════════════════════════════
 * REST API — /starter/v1/page-meta
 *
 * Uses carbon_set_post_meta() / carbon_get_post_meta() for proper CF storage.
 * ══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('starter_rest_all_allowed_fields')) {
    function starter_rest_all_allowed_fields(): array
    {
        static $fields = null;
        if ($fields !== null) {
            return $fields;
        }
        $fields = array_values(array_unique(array_merge(
            array_keys(starter_all_section_meta_keys()),
            array('starter_page_family', 'starter_page_sections')
        )));
        return $fields;
    }
}

add_action('rest_api_init', function () {
    register_rest_route('starter/v1', '/page-meta', array(
        array(
            'methods' => 'GET',
            'callback' => 'starter_rest_get_page_meta',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ),
        array(
            'methods' => 'POST',
            'callback' => 'starter_rest_set_page_meta',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ),
    ));
});

if (!function_exists('starter_rest_find_page')) {
    function starter_rest_find_page(string $path): ?WP_Post
    {
        $normalized = trim($path, '/');
        if ($normalized === '') {
            return null;
        }
        $page = get_page_by_path($normalized);
        if ($page instanceof WP_Post) {
            return $page;
        }
        $pages = get_posts(array(
            'post_type' => 'page',
            'name' => basename($normalized),
            'post_status' => 'publish',
            'numberposts' => 1,
        ));
        return !empty($pages) ? $pages[0] : null;
    }
}

if (!function_exists('starter_rest_get_page_meta')) {
    function starter_rest_get_page_meta(WP_REST_Request $request): WP_REST_Response
    {
        $path = trim((string) $request->get_param('path'));
        if ($path === '') {
            return new WP_REST_Response(array('error' => 'Missing param: path'), 400);
        }
        $page = starter_rest_find_page($path);
        if (!$page) {
            return new WP_REST_Response(array('error' => 'Page not found: ' . $path), 404);
        }
        $allowed = starter_rest_all_allowed_fields();
        $fields_param = $request->get_param('fields');
        $requested = is_string($fields_param) && trim($fields_param) !== ''
            ? array_filter(array_map('trim', explode(',', $fields_param)))
            : $allowed;

        $data = array();
        foreach ($requested as $f) {
            if (in_array($f, $allowed, true)) {
                $data[$f] = carbon_get_post_meta((int) $page->ID, $f);
            }
        }
        return new WP_REST_Response(array('path' => $path, 'page_id' => (int) $page->ID, 'fields' => $data), 200);
    }
}

if (!function_exists('starter_rest_set_page_meta')) {
    function starter_rest_set_page_meta(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params();
        if (empty($payload) || !is_array($payload)) {
            return new WP_REST_Response(array('error' => 'Body must be JSON object.'), 400);
        }
        $updates = $payload['updates'] ?? null;
        if (!is_array($updates) || empty($updates)) {
            return new WP_REST_Response(array('error' => 'Missing updates array.'), 400);
        }

        $dry_run = !empty($payload['dry_run']);
        $allowed = starter_rest_all_allowed_fields();
        $report = array('dry_run' => $dry_run, 'updated' => array(), 'errors' => array());

        foreach ($updates as $i => $entry) {
            if (!is_array($entry)) {
                $report['errors'][] = 'Invalid entry at ' . $i;
                continue;
            }
            $path = trim((string) ($entry['path'] ?? ''));
            $fields = $entry['fields'] ?? null;
            if ($path === '' || !is_array($fields) || empty($fields)) {
                $report['errors'][] = 'Entry ' . $i . ' needs path + fields.';
                continue;
            }
            $page = starter_rest_find_page($path);
            if (!$page) {
                $report['errors'][] = 'Page not found: ' . $path;
                continue;
            }
            foreach ($fields as $fname => $value) {
                $fname = trim((string) $fname);
                if (!in_array($fname, $allowed, true)) {
                    $report['errors'][] = 'Unknown field: ' . $fname;
                    continue;
                }
                if (!$dry_run) {
                    carbon_set_post_meta((int) $page->ID, $fname, $value);
                }
                $report['updated'][] = array('path' => $path, 'page_id' => (int) $page->ID, 'field' => $fname);
            }
        }

        return new WP_REST_Response($report, empty($report['errors']) ? 200 : 207);
    }
}

/* ══════════════════════════════════════════════════════════════════════════
 * Carbon Fields Container Registration (dynamic from field maps)
 * ══════════════════════════════════════════════════════════════════════════ */

add_action('carbon_fields_register_fields', function () {
    $maps = starter_page_field_maps();

    foreach ($maps as $slug => $sections) {
        $page = get_page_by_path($slug);
        if (!$page) {
            continue;
        }

        $page_ids = array((int) $page->ID);
        if (has_filter('wpml_object_id') && has_filter('wpml_active_languages')) {
            $languages = apply_filters('wpml_active_languages', null, array('skip_missing' => 0));
            if (is_array($languages)) {
                foreach (array_keys($languages) as $lc) {
                    $tid = apply_filters('wpml_object_id', $page->ID, 'page', false, (string) $lc);
                    if (is_numeric($tid) && (int) $tid > 0) {
                        $page_ids[] = (int) $tid;
                    }
                }
            }
        }
        $page_ids = array_values(array_unique($page_ids));

        $label = ucwords($slug) . ' Page — Sections';
        $container = Container::make('post_meta', $label)
            ->where('post_type', '=', 'page')
            ->where('post_id', '=', $page_ids[0]);

        foreach (array_slice($page_ids, 1) as $eid) {
            $container->or_where('post_id', '=', $eid);
        }

        foreach ($sections as $idx => $sdef) {
            $tab = ucwords(str_replace('-', ' ', $sdef['type'])) . ' (#' . ($idx + 1) . ')';
            $tab_fields = array();
            foreach (($sdef['fields'] ?? array()) as $fname => $fdef) {
                $mk = $sdef['prefix'] . '_' . $fname;
                $ul = ucwords(str_replace('_', ' ', $fname));
                $st = starter_field_storage_type($fdef);
                if ($st === 'textarea' || $st === 'json') {
                    $f = Field::make('textarea', $mk, $ul)->set_rows($st === 'json' ? 6 : 3);
                    if ($st === 'json') {
                        $f->set_help_text('JSON array. Managed by seed scripts.');
                    }
                } else {
                    $f = Field::make('text', $mk, $ul);
                }
                $tab_fields[] = $f;
            }
            $container->add_tab($tab, $tab_fields);
        }
    }
});
