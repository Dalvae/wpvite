<?php
/**
 * Carbon Fields — boot + base page fields.
 *
 * Two base fields used by all pages:
 *   - starter_page_family    → template family name (e.g. "section-stack")
 *   - starter_page_sections  → JSON blob fallback for pages without per-field maps
 *
 * Per-field definitions live in inc/carbon-fields-page-fields.php.
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('after_setup_theme', function () {
    \Carbon_Fields\Carbon_Fields::boot();
});

add_action('carbon_fields_register_fields', function () {
    Container::make('post_meta', __('Page Sections', 'starter'))
        ->where('post_type', '=', 'page')
        ->add_fields(array(
            Field::make('text', 'starter_page_family', __('Page Family', 'starter'))
                ->set_help_text('Template family: section-stack.'),

            Field::make('textarea', 'starter_page_sections', __('Sections JSON', 'starter'))
                ->set_rows(8)
                ->set_help_text('JSON blob fallback. Pages with per-field maps ignore this.'),
        ));
});
