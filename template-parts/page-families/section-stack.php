<?php
$args = wp_parse_args(
    $args,
    array(
        'sections' => array(),
    )
);

starter_render_sections((array) $args['sections']);
