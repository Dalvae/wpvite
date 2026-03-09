<?php
get_template_part(
    'components/section-scene',
    null,
    starter_get_section_scene_args(
        'hero-centered',
        is_array($args) ? $args : array(),
        array(
            'body_args' => wp_parse_args(
                is_array($args) ? $args : array(),
                array(
                    'header_preset' => 'centered-display',
                )
            ),
        )
    )
);
