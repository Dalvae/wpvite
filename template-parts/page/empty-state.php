<?php
$args = wp_parse_args(
    $args,
    array(
        'title' => '',
        'summary' => '',
        'class' => '',
    )
);

$empty_state_args = starter_get_page_empty_state_args($args);
$section_classes = starter_get_page_surface_classes((string) $empty_state_args['surface_variant'], (string) $empty_state_args['class']);
$header_args = starter_get_page_section_header_args(
    'empty-state',
    array(
        'title' => (string) $empty_state_args['title'],
        'intro' => (string) $empty_state_args['summary'],
        'attributes' => array(
            'data-reveal' => 'true',
        ),
    )
);
?>
<section class="<?php echo esc_attr($section_classes); ?>">
    <?php get_template_part('components/section-header', null, $header_args); ?>
</section>
