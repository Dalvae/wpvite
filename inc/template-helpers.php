<?php

if (!function_exists('starter_merge_classes')) {
    function starter_merge_classes(...$values): string
    {
        $classes = array();

        foreach ($values as $value) {
            if (is_array($value)) {
                foreach ($value as $nested_value) {
                    $nested_classes = explode(' ', trim((string) $nested_value));
                    foreach ($nested_classes as $nested_class) {
                        if ($nested_class !== '') {
                            $classes[] = $nested_class;
                        }
                    }
                }

                continue;
            }

            $string_value = trim((string) $value);
            if ($string_value === '') {
                continue;
            }

            foreach (explode(' ', $string_value) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $classes[] = $part;
                }
            }
        }

        return implode(' ', array_values(array_unique($classes)));
    }
}

if (!function_exists('starter_render_html_attributes')) {
    function starter_render_html_attributes(array $attributes): string
    {
        $compiled = array();

        foreach ($attributes as $name => $value) {
            if (!is_string($name) || $name === '' || $value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $compiled[] = esc_attr($name);
                continue;
            }

            if (is_array($value)) {
                $value = implode(' ', array_filter(array_map('trim', array_map('strval', $value))));
            }

            $compiled[] = sprintf('%s="%s"', esc_attr($name), esc_attr((string) $value));
        }

        return implode(' ', $compiled);
    }
}

if (!function_exists('starter_get_icon_svg')) {
    function starter_get_icon_svg(string $name, array $args = array()): string
    {
        if (!function_exists('starter_icon')) {
            return '';
        }

        return starter_icon(
            $name,
            wp_parse_args(
                $args,
                array(
                    'class' => '',
                    'size' => '1em',
                    'decorative' => true,
                    'label' => '',
                )
            )
        );
    }
}

if (!function_exists('starter_get_button_classes')) {
    function starter_get_button_classes(string $variant = 'primary', string $size = 'md', string $radius = 'md', string $extra_classes = ''): string
    {
        $variant_classes = array(
            'primary' => 'btn-primary',
            'secondary' => 'btn-neutral',
            'tertiary' => 'btn-accent',
            'warning' => 'btn-warning',
            'success' => 'btn-success',
            'danger' => 'btn-error',
            'ghost' => 'btn-ghost border border-base-300 bg-base-100 text-base-content hover:border-base-300 hover:bg-base-200',
        );
        $size_classes = array(
            'sm' => 'btn-sm',
            'md' => '',
            'lg' => 'btn-lg',
        );
        $radius_classes = array(
            'sm' => 'rounded-[var(--ds-radius-button-sm)]',
            'md' => 'rounded-[var(--ds-radius-button-md)]',
            'lg' => 'rounded-[var(--ds-radius-button-lg)]',
            'pill' => 'rounded-[var(--ds-radius-pill)]',
        );

        $resolved_variant = array_key_exists($variant, $variant_classes) ? $variant : 'primary';
        $resolved_size = array_key_exists($size, $size_classes) ? $size : 'md';
        $resolved_radius = array_key_exists($radius, $radius_classes) ? $radius : 'md';

        return starter_merge_classes(
            'btn starter-btn no-underline shadow-none hover:no-underline',
            $variant_classes[$resolved_variant],
            $size_classes[$resolved_size],
            $radius_classes[$resolved_radius],
            $extra_classes
        );
    }
}

if (!function_exists('starter_get_icon_button_classes')) {
    function starter_get_icon_button_classes(string $variant = 'surface', string $extra_classes = ''): string
    {
        $variants = array(
            'surface' => 'btn-ghost border border-base-300 bg-base-100 text-primary hover:border-base-300 hover:bg-base-200',
            'plain' => 'btn-ghost border border-transparent bg-transparent text-current hover:bg-transparent hover:text-primary',
            'info-soft' => 'border border-transparent bg-info/10 text-info hover:bg-info/20',
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'surface';

        return starter_merge_classes(
            'btn btn-square starter-icon-button shadow-none',
            $variants[$resolved_variant],
            $extra_classes
        );
    }
}

if (!function_exists('starter_get_badge_classes')) {
    function starter_get_badge_classes(string $variant = 'surface', string $extra_classes = ''): string
    {
        $variants = array(
            'surface' => 'border-base-300 bg-base-100 text-base-content',
            'accent-soft' => 'border-transparent bg-primary/12 text-primary',
            'success-soft' => 'border-transparent bg-success/14 text-success',
            'warning-soft' => 'border-transparent bg-warning/16 text-warning-content',
            'danger-soft' => 'border-transparent bg-error/14 text-error',
            'info-soft' => 'border-transparent bg-info/12 text-info',
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'surface';

        return starter_merge_classes(
            'badge starter-badge',
            $variants[$resolved_variant],
            $extra_classes
        );
    }
}

if (!function_exists('starter_get_panel_classes')) {
    function starter_get_panel_classes(string $variant = 'soft', string $extra_classes = ''): string
    {
        $variants = array(
            'soft' => 'bg-base-100/90',
            'muted' => 'bg-base-200/92',
            'tint' => 'bg-primary/8',
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'soft';

        return starter_merge_classes(
            'ui-panel card border border-base-300 shadow-xl backdrop-blur-sm',
            $variants[$resolved_variant],
            $extra_classes
        );
    }
}

if (!function_exists('starter_get_input_classes')) {
    function starter_get_input_classes(string $extra_classes = ''): string
    {
        return starter_merge_classes(
            'input input-bordered starter-field w-full bg-base-100/95',
            $extra_classes
        );
    }
}

if (!function_exists('starter_get_textarea_classes')) {
    function starter_get_textarea_classes(string $extra_classes = ''): string
    {
        return starter_merge_classes(
            'textarea textarea-bordered starter-field w-full bg-base-100/95',
            $extra_classes
        );
    }
}

if (!function_exists('starter_get_select_classes')) {
    function starter_get_select_classes(string $extra_classes = ''): string
    {
        return starter_merge_classes(
            'select select-bordered starter-field w-full bg-base-100/95',
            $extra_classes
        );
    }
}

if (!function_exists('starter_get_text_link_classes')) {
    function starter_get_text_link_classes(string $variant = 'strong', string $extra_classes = ''): string
    {
        $variants = array('strong', 'subtle');
        $resolved_variant = in_array($variant, $variants, true) ? $variant : 'strong';

        return starter_merge_classes(
            'ui-text-link',
            'ui-text-link--' . $resolved_variant,
            $extra_classes
        );
    }
}

if (!function_exists('starter_get_scene_shell_classes')) {
    function starter_get_scene_shell_classes(string $variant = 'brand', string $extra_classes = ''): string
    {
        $variants = array(
            'brand' => 'brand-shell',
            'contained' => 'scene-shell scene-shell--contained',
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'brand';

        return starter_merge_classes($variants[$resolved_variant], $extra_classes);
    }
}

if (!function_exists('starter_get_scene_surface_classes')) {
    function starter_get_scene_surface_classes(string $variant = 'plain', string $extra_classes = ''): string
    {
        $variants = array(
            'plain' => '',
            'muted' => 'section-scene--surface-muted',
            'elevated' => 'section-scene--surface-elevated',
            'tint' => 'section-scene--surface-tint',
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'plain';

        return starter_merge_classes($variants[$resolved_variant], $extra_classes);
    }
}

if (!function_exists('starter_get_scene_spacing_classes')) {
    function starter_get_scene_spacing_classes(string $variant = 'scene', string $extra_classes = ''): string
    {
        $variants = array(
            'none' => '',
            'intro' => 'section-scene--spacing-intro',
            'compact' => 'section-scene--spacing-compact',
            'stack' => 'section-scene--spacing-stack',
            'scene' => 'section-scene--spacing-scene',
            'fullscreen' => 'section-scene--spacing-fullscreen',
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'scene';

        return starter_merge_classes($variants[$resolved_variant], $extra_classes);
    }
}

if (!function_exists('starter_get_scene_divider_classes')) {
    function starter_get_scene_divider_classes(string $variant = 'none', string $extra_classes = ''): string
    {
        $variants = array(
            'none' => '',
            'top' => 'section-scene--divider-top',
            'soft-top' => 'section-scene--divider-soft-top',
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'none';

        return starter_merge_classes($variants[$resolved_variant], $extra_classes);
    }
}

if (!function_exists('starter_get_section_scene_classes')) {
    function starter_get_section_scene_classes(string $surface = 'plain', string $spacing = 'scene', string $extra_classes = '', string $divider = 'none'): string
    {
        return starter_merge_classes(
            'section-scene',
            starter_get_scene_surface_classes($surface),
            starter_get_scene_spacing_classes($spacing),
            starter_get_scene_divider_classes($divider),
            $extra_classes
        );
    }
}

if (!function_exists('starter_get_scene_layout_variants')) {
    function starter_get_scene_layout_variants(): array
    {
        return array(
            'stack' => 'scene-layout scene-layout--stack',
            'split' => 'scene-layout scene-layout--split',
            'cards' => 'scene-layout scene-layout--cards',
            'hero' => 'scene-layout scene-layout--hero',
            'sidebar-feature' => 'scene-layout scene-layout--sidebar-feature',
            'centered' => 'scene-layout scene-layout--centered',
        );
    }
}

if (!function_exists('starter_is_scene_layout_variant')) {
    function starter_is_scene_layout_variant(string $variant): bool
    {
        return array_key_exists($variant, starter_get_scene_layout_variants());
    }
}

if (!function_exists('starter_get_scene_layout_classes')) {
    function starter_get_scene_layout_classes(string $variant = 'stack', string $extra_classes = ''): string
    {
        $variants = starter_get_scene_layout_variants();
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'stack';

        return starter_merge_classes($variants[$resolved_variant], $extra_classes);
    }
}

if (!function_exists('starter_get_content_stack_classes')) {
    function starter_get_content_stack_classes(string $variant = 'body', string $extra_classes = ''): string
    {
        $variants = array(
            'body' => 'content-stack content-stack--body',
            'copy' => 'content-stack content-stack--copy',
            'section' => 'content-stack content-stack--section',
            'section-list' => 'content-stack content-stack--section-list',
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'body';

        return starter_merge_classes($variants[$resolved_variant], $extra_classes);
    }
}

if (!function_exists('starter_get_section_header_classes')) {
    function starter_get_section_header_classes(string $layout = 'default', string $extra_classes = ''): string
    {
        $layouts = array(
            'default' => '',
            'scene' => 'ui-section-header--scene',
            'centered' => 'ui-section-header--centered',
            'sidebar' => 'ui-section-header--sidebar',
        );
        $resolved_layout = array_key_exists($layout, $layouts) ? $layout : 'default';

        return starter_merge_classes($layouts[$resolved_layout], $extra_classes);
    }
}

if (!function_exists('starter_get_section_header_preset_args')) {
    function starter_get_section_header_preset_args(string $preset = 'default', array $overrides = array()): array
    {
        $presets = array(
            'default' => array(),
            'page-intro' => array(
                'layout' => 'default',
                'title_family' => 'page',
                'title_tone' => 'default',
                'intro_family' => 'page',
                'intro_tone' => 'muted',
            ),
            'page-section' => array(
                'layout' => 'default',
                'title_family' => 'section',
                'title_tone' => 'default',
                'intro_family' => 'section',
                'intro_tone' => 'muted',
            ),
            'empty-state' => array(
                'layout' => 'centered',
                'title_family' => 'section',
                'title_tone' => 'default',
                'intro_family' => 'section',
                'intro_tone' => 'muted',
            ),
            'centered-display' => array(
                'layout' => 'centered',
                'title_family' => 'display',
                'title_tone' => 'default',
                'intro_family' => 'section',
                'intro_tone' => 'muted',
            ),
            'scene-display' => array(
                'layout' => 'scene',
                'title_family' => 'display',
                'title_tone' => 'default',
                'intro_family' => 'section',
                'intro_tone' => 'muted',
            ),
            'sidebar-section' => array(
                'layout' => 'sidebar',
                'title_family' => 'section',
                'title_tone' => 'default',
                'intro_family' => 'section',
                'intro_tone' => 'muted',
            ),
        );

        $resolved_preset = array_key_exists($preset, $presets) ? $preset : 'default';

        return array_replace_recursive($presets[$resolved_preset], $overrides);
    }
}

if (!function_exists('starter_get_section_title_classes')) {
    function starter_get_section_title_classes(string $family = 'section', string $tone = 'default', string $extra_classes = ''): string
    {
        $families = array(
            'default' => 'ui-section-title',
            'page' => 'ui-section-title ui-section-title--page',
            'section' => 'ui-section-title ui-section-title--section',
            'display' => 'ui-section-title ui-section-title--display',
        );
        $tones = array(
            'default' => '',
            'muted' => 'ui-section-title--muted',
            'inverse' => 'ui-section-title--inverse',
        );

        $resolved_family = array_key_exists($family, $families) ? $family : 'section';
        $resolved_tone = array_key_exists($tone, $tones) ? $tone : 'default';

        return starter_merge_classes($families[$resolved_family], $tones[$resolved_tone], $extra_classes);
    }
}

if (!function_exists('starter_get_section_intro_classes')) {
    function starter_get_section_intro_classes(string $family = 'section', string $tone = 'default', string $extra_classes = ''): string
    {
        $families = array(
            'default' => 'ui-section-intro',
            'page' => 'ui-section-intro ui-section-intro--page',
            'section' => 'ui-section-intro ui-section-intro--section',
            'lead' => 'ui-section-intro ui-section-intro--lead',
        );
        $tones = array(
            'default' => '',
            'muted' => 'ui-section-intro--muted',
            'inverse' => 'ui-section-intro--inverse',
        );

        $resolved_family = array_key_exists($family, $families) ? $family : 'section';
        $resolved_tone = array_key_exists($tone, $tones) ? $tone : 'default';

        return starter_merge_classes($families[$resolved_family], $tones[$resolved_tone], $extra_classes);
    }
}
