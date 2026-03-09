<?php
$args = wp_parse_args(
    $args,
    array(
        'title' => '',
        'summary' => '',
        'links' => array(),
        'class' => '',
    )
);

$contact_links = is_array($args['links']) ? $args['links'] : array();
$section_classes = starter_get_page_surface_classes('content', (string) $args['class']);
$header_args = starter_get_page_section_header_args(
    'section',
    array(
        'title' => (string) $args['title'],
        'intro' => (string) $args['summary'],
    )
);
$contact_list_args = starter_get_page_contact_list_args($contact_links);
?>
<section class="<?php echo esc_attr($section_classes); ?>" data-reveal="true">
    <?php get_template_part('components/section-header', null, $header_args); ?>

    <?php if (!empty($contact_links)) : ?>
        <?php get_template_part('components/contact-list', null, $contact_list_args); ?>
    <?php endif; ?>
</section>
