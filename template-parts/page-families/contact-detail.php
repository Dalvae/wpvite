<?php
$args = wp_parse_args(
    $args,
    array(
        'eyebrow' => '',
        'title' => '',
        'summary' => '',
        'links' => array(),
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
        'template-parts/page/contact-panel',
        null,
        array(
            'title' => (string) $args['title'],
            'summary' => (string) $args['summary'],
            'links' => (array) $args['links'],
        )
    );
    ?>
</div>
