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

