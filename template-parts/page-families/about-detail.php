<?php
$args = wp_parse_args(
    $args,
    array(
        'eyebrow' => '',
        'title' => '',
        'summary' => '',
        'content' => '',
        'highlights_title' => __('Highlights', 'wpvite'),
        'highlights' => array(),
        'contact_title' => '',
        'contact_summary' => '',
        'contact_links' => array(),
        'class' => '',
    )
);

$highlights = array_values(array_filter((array) $args['highlights'], 'is_string'));
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

    <?php if (trim((string) $args['content']) !== '') : ?>
        <section class="<?php echo esc_attr(starter_get_page_surface_classes('content')); ?>" data-reveal="true">
            <div class="page-prose">
                <?php echo wp_kses_post(wpautop((string) $args['content'])); ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($highlights)) : ?>
        <section class="<?php echo esc_attr(starter_get_page_surface_classes('content')); ?>" data-reveal="true">
            <?php
            get_template_part(
                'components/section-header',
                null,
                starter_get_page_section_header_args(
                    'section',
                    array(
                        'title' => (string) $args['highlights_title'],
                    )
                )
            );
            ?>
            <ul class="detail-list">
                <?php foreach ($highlights as $highlight) : ?>
                    <li class="detail-list__item">
                        <?php echo esc_html($highlight); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

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
