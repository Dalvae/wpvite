<?php
/**
 * Legacy ACF page builder adapter.
 *
 * The historic builder loads `builder/_{layout}.php` partials. New reusable
 * starter sections should render through starter_render_section(); when a
 * flexible-content row layout matches a registered starter section (allowing
 * underscores in ACF layout names), route it there and leave all other layouts
 * on the legacy path for backwards compatibility.
 */

if (!function_exists('starter_builder_get_section_type')) {
    function starter_builder_get_section_type(string $layout): string
    {
        $section_type = sanitize_key(str_replace('_', '-', $layout));

        if ($section_type === '' || !function_exists('starter_render_section')) {
            return '';
        }

        if (function_exists('starter_get_section_registry')) {
            $registry = starter_get_section_registry();
            return isset($registry[$section_type]) ? $section_type : '';
        }

        if (function_exists('starter_get_section_template_path')) {
            return starter_get_section_template_path($section_type) !== '' ? $section_type : '';
        }

        return '';
    }
}

if (!function_exists('starter_builder_get_section_args')) {
    function starter_builder_get_section_args(string $section_type): array
    {
        $args = function_exists('get_row') ? get_row(true) : array();
        $args = is_array($args) ? $args : array();

        // These keys identify the flexible-content row/section and are not
        // part of the reusable section body args consumed by section templates.
        unset($args['acf_fc_layout'], $args['layout'], $args['type'], $args['section']);

        /**
         * Adapter extension point for projects that need to normalize legacy
         * ACF field names before they reach starter_render_section().
         */
        return apply_filters('starter_builder_section_args', $args, $section_type);
    }
}

if (have_rows('builder')): ?>
    <div id="builder">
        <?php
        $builder = 0;
        while (have_rows('builder')): the_row();
            $builder++;
            $layout = sanitize_key((string) get_row_layout());
            $section_type = starter_builder_get_section_type($layout);
            $layout_path = get_template_directory() . '/builder/_' . $layout . '.php';
            ?>
            <?php if ($section_type !== ''): ?>
                <?php starter_render_section($section_type, starter_builder_get_section_args($section_type)); ?>
                <?php continue; ?>
            <?php endif; ?>
            <section class="<?php echo esc_attr($layout); ?> builder<?php echo esc_attr((string) $builder); ?>">
                <?php
                if (file_exists($layout_path)) require($layout_path);
                else echo '<strong>[builder error: Block "_' . $layout . '.php" could not be found]</strong>';
                ?>
            </section>
        <?php endwhile; ?>
    </div>
<?php endif; ?>
