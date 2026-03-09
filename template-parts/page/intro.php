<?php
$args = wp_parse_args(
    $args,
    array(
        'eyebrow' => '',
        'title' => '',
        'summary' => '',
        'title_tag' => 'h1',
        'class' => '',
    )
);

$title_tag = strtolower(trim((string) $args['title_tag']));
if (!in_array($title_tag, array('h1', 'h2', 'h3'), true)) {
    $title_tag = 'h1';
}

$section_classes = starter_get_page_surface_classes('content', (string) $args['class']);
$header_args = starter_get_page_section_header_args(
    'intro',
    array(
        'kicker' => (string) $args['eyebrow'],
        'title' => (string) $args['title'],
        'intro' => (string) $args['summary'],
        'title_tag' => $title_tag,
    )
);
?>
<section class="<?php echo esc_attr($section_classes); ?>" data-reveal="true">
    <?php get_template_part('components/section-header', null, $header_args); ?>
</section>
