<?php

if (!function_exists('starter_get_entry_title_or_fallback')) {
    function starter_get_entry_title_or_fallback(int $post_id, string $fallback = ''): string
    {
        $title = trim((string) get_the_title($post_id));

        return $title !== '' ? $title : trim($fallback);
    }
}

if (!function_exists('starter_get_entry_summary')) {
    function starter_get_entry_summary(int $post_id, int $word_limit = 28): string
    {
        if ($post_id <= 0) {
            return '';
        }

        $excerpt = trim((string) get_the_excerpt($post_id));
        if ($excerpt !== '') {
            return wp_trim_words(wp_strip_all_tags($excerpt), $word_limit);
        }

        $content = trim((string) get_post_field('post_content', $post_id));
        if ($content !== '') {
            return wp_trim_words(wp_strip_all_tags($content), $word_limit);
        }

        return '';
    }
}

if (!function_exists('starter_merge_page_args')) {
    function starter_merge_page_args(array $defaults, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])) {
                $defaults[$key] = starter_merge_page_args($defaults[$key], $value);
                continue;
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }
}

if (!function_exists('starter_get_page_shell_classes')) {
    function starter_get_page_shell_classes(string $extra_classes = ''): string
    {
        return starter_merge_classes('brand-shell', 'page-shell', $extra_classes);
    }
}

if (!function_exists('starter_get_content_card_classes')) {
    function starter_get_content_card_classes(string $extra_classes = ''): string
    {
        return starter_merge_classes(
            'content-card card border border-base-300 bg-base-100/90 shadow-xl backdrop-blur-sm',
            $extra_classes
        );
    }
}

if (!function_exists('starter_get_page_surface_classes')) {
    function starter_get_page_surface_classes(string $variant = 'content', string $extra_classes = ''): string
    {
        $variants = array(
            'content' => starter_get_content_card_classes(),
            'summary-card' => starter_get_content_card_classes('content-card--summary'),
            'empty-state' => starter_get_content_card_classes('content-card--empty'),
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'content';

        return starter_merge_classes($variants[$resolved_variant], $extra_classes);
    }
}

if (!function_exists('starter_get_collection_grid_classes')) {
    function starter_get_collection_grid_classes(string $variant = 'stack', string $extra_classes = ''): string
    {
        $variants = array(
            'stack' => 'page-collection page-collection--stack',
            'cards' => 'page-collection page-collection--cards',
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'stack';

        return starter_merge_classes($variants[$resolved_variant], $extra_classes);
    }
}

if (!function_exists('starter_get_page_section_header_args')) {
    function starter_get_page_section_header_args(string $variant = 'intro', array $overrides = array()): array
    {
        $variants = array(
            'intro' => array(
                'kicker' => '',
                'title' => '',
                'intro' => '',
                'title_tag' => 'h1',
                'preset' => 'page-intro',
            ),
            'section' => array(
                'kicker' => '',
                'title' => '',
                'intro' => '',
                'title_tag' => 'h2',
                'preset' => 'page-section',
            ),
            'empty-state' => array(
                'kicker' => '',
                'title' => '',
                'intro' => '',
                'title_tag' => 'h2',
                'preset' => 'empty-state',
            ),
        );
        $resolved_variant = array_key_exists($variant, $variants) ? $variant : 'intro';

        return starter_merge_page_args($variants[$resolved_variant], $overrides);
    }
}

if (!function_exists('starter_get_page_empty_state_args')) {
    function starter_get_page_empty_state_args(array $overrides = array()): array
    {
        $defaults = array(
            'title' => '',
            'summary' => '',
            'surface_variant' => 'empty-state',
            'class' => '',
        );

        return starter_merge_page_args($defaults, $overrides);
    }
}

if (!function_exists('starter_get_page_collection_section_args')) {
    function starter_get_page_collection_section_args(string $variant = 'stack', array $overrides = array()): array
    {
        $defaults = array(
            'grid_class' => '',
            'aria_label' => '',
            'empty_title' => '',
            'empty_summary' => '',
        );
        $resolved_args = starter_merge_page_args($defaults, $overrides);
        $section_tag = trim((string) $resolved_args['aria_label']) !== '' ? 'section' : 'div';
        $section_attributes = array(
            'class' => starter_get_collection_grid_classes($variant, (string) $resolved_args['grid_class']),
        );

        if (trim((string) $resolved_args['aria_label']) !== '') {
            $section_attributes['aria-label'] = (string) $resolved_args['aria_label'];
        }

        return array(
            'section_tag' => $section_tag,
            'section_attributes' => $section_attributes,
            'empty_state' => starter_get_page_empty_state_args(
                array(
                    'title' => (string) $resolved_args['empty_title'],
                    'summary' => (string) $resolved_args['empty_summary'],
                )
            ),
        );
    }
}

if (!function_exists('starter_get_page_contact_list_args')) {
    function starter_get_page_contact_list_args(array $links): array
    {
        $items = array();

        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }

            $label = trim((string) ($link['label'] ?? $link['text'] ?? ''));
            $href = trim((string) ($link['href'] ?? ''));
            $icon = trim((string) ($link['icon'] ?? ''));

            if ($label === '') {
                continue;
            }

            if ($icon === '') {
                if (strpos($href, 'mailto:') === 0) {
                    $icon = 'mail';
                } elseif (strpos($href, 'tel:') === 0) {
                    $icon = 'phone';
                } elseif ($href !== '') {
                    $icon = 'globe';
                }
            }

            $items[] = array(
                'label' => $label,
                'href' => $href,
                'icon' => $icon,
                'class' => (string) ($link['class'] ?? ''),
            );
        }

        return array(
            'items' => $items,
        );
    }
}

if (!function_exists('starter_get_summary_card_component_args')) {
    function starter_get_summary_card_component_args(array $overrides = array(), int $word_limit = 24): array
    {
        $defaults = array(
            'post_id' => 0,
            'title' => '',
            'url' => '',
            'summary' => '',
            'meta' => '',
            'action_label' => __('Learn more', 'wpvite'),
            'image_url' => '',
            'image_alt' => '',
            'title_tag' => 'h2',
            'class' => '',
        );
        $args = starter_merge_page_args($defaults, $overrides);
        $post_id = (int) $args['post_id'];

        if ($post_id > 0) {
            if (trim((string) $args['title']) === '') {
                $args['title'] = starter_get_entry_title_or_fallback($post_id);
            }

            if (trim((string) $args['url']) === '') {
                $args['url'] = (string) get_permalink($post_id);
            }

            if (trim((string) $args['summary']) === '') {
                $args['summary'] = starter_get_entry_summary($post_id, $word_limit);
            }

            if (trim((string) $args['meta']) === '' && get_post_type($post_id) === 'post') {
                $args['meta'] = (string) get_the_date('', $post_id);
            }
        }

        return $args;
    }
}
