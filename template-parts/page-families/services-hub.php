<?php
$args = wp_parse_args(
    $args,
    array(
        'eyebrow' => '',
        'title' => '',
        'summary' => '',
        'items' => array(),
        'empty_title' => __('No services published yet.', 'wpvite'),
        'empty_summary' => __('Add service cards or pass an items array to render this family.', 'wpvite'),
        'contact_title' => '',
        'contact_summary' => '',
        'contact_links' => array(),
        'class' => '',
    )
);
?>
<div class="<?php echo esc_attr(starter_get_page_shell_classes((string) $args['class'])); ?>">
    <?php
    get_template_part(
        'template-parts/page/intro',
        null,
        array(
            'eyebrow' => (string) $args['eyebrow'],
            'title' => (string) $args['title'],
            'summary' => (string) $args['summary'],
        )
    );
    ?>

    <?php
    get_template_part(
        'components/section-scene',
        null,
        array(
            'spacing_variant' => 'stack',
            'body_template' => 'template-parts/page/summary-grid',
            'body_args' => array(
                'items' => (array) $args['items'],
                'empty_title' => (string) $args['empty_title'],
                'empty_summary' => (string) $args['empty_summary'],
            ),
        )
    );
    ?>

    <?php if (!empty($args['contact_links'])) : ?>
        <?php
        get_template_part(
            'template-parts/page/contact-panel',
            null,
            array(
                'title' => (string) $args['contact_title'],
                'summary' => (string) $args['contact_summary'],
                'links' => (array) $args['contact_links'],
            )
        );
        ?>
    <?php endif; ?>
</div>
