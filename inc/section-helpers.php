<?php

if (!function_exists('starter_get_section_registry')) {
    function starter_get_section_registry(): array
    {
        return array(
            'hero-split' => array(
                'surface_variant' => 'plain',
                'spacing_variant' => 'fullscreen',
                'layout_variant' => 'hero',
                'body_template' => 'components/section-bodies/hero',
            ),
            'hero-centered' => array(
                'surface_variant' => 'plain',
                'spacing_variant' => 'fullscreen',
                'layout_variant' => 'centered',
                'body_template' => 'components/section-bodies/hero',
            ),
            'stats-band' => array(
                'surface_variant' => 'muted',
                'spacing_variant' => 'stack',
                'layout_variant' => 'stack',
                'body_template' => 'components/section-bodies/stats-band',
            ),
            'offer-cards' => array(
                'surface_variant' => 'plain',
                'spacing_variant' => 'scene',
                'layout_variant' => 'stack',
                'body_template' => 'components/section-bodies/offer-cards',
            ),
            'proof-results' => array(
                'surface_variant' => 'plain',
                'spacing_variant' => 'stack',
                'layout_variant' => 'stack',
                'body_template' => 'components/section-bodies/proof-results',
            ),
            'faq-accordion' => array(
                'surface_variant' => 'plain',
                'spacing_variant' => 'stack',
                'layout_variant' => 'stack',
                'body_template' => 'components/section-bodies/faq-accordion',
            ),
            'final-cta' => array(
                'surface_variant' => 'tint',
                'spacing_variant' => 'scene',
                'layout_variant' => 'stack',
                'body_template' => 'components/section-bodies/final-cta',
            ),
        );
    }
}

if (!function_exists('starter_get_section_scene_args')) {
    function starter_get_section_scene_args(string $type, array $body_args = array(), array $overrides = array()): array
    {
        $registry = starter_get_section_registry();
        $defaults = $registry[$type] ?? array();

        return starter_merge_page_args(
            array(
                'section_tag' => 'section',
                'surface_variant' => 'plain',
                'spacing_variant' => 'scene',
                'divider_variant' => 'none',
                'layout_variant' => 'stack',
                'shell_variant' => 'brand',
                'content_class' => '',
                'body_template' => '',
                'body_args' => $body_args,
                'attributes' => array(),
                'content_attributes' => array(),
            ),
            array_replace_recursive($defaults, $overrides)
        );
    }
}

if (!function_exists('starter_split_stat_value')) {
    function starter_split_stat_value(string $value): array
    {
        if ($value === '') {
            return array();
        }

        $parts = preg_split('/(\d+(?:\.\d+)?)/', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts)) {
            return array(
                array(
                    'type' => 'text',
                    'raw' => $value,
                ),
            );
        }

        $value_parts = array();
        foreach ($parts as $part) {
            if (preg_match('/^\d+(?:\.\d+)?$/', $part) === 1) {
                $decimals = 0;
                if (strpos($part, '.') !== false) {
                    $segments = explode('.', $part);
                    $decimals = isset($segments[1]) ? strlen($segments[1]) : 0;
                }

                $value_parts[] = array(
                    'type' => 'number',
                    'raw' => $part,
                    'value' => (float) $part,
                    'decimals' => $decimals,
                );
                continue;
            }

            $normalized_part = preg_replace('/\s+/u', ' ', trim($part));
            if (!is_string($normalized_part) || $normalized_part === '') {
                continue;
            }

            $value_parts[] = array(
                'type' => 'text',
                'raw' => $normalized_part,
            );
        }

        return $value_parts;
    }
}
