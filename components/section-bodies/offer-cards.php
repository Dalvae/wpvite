<?php
$args = wp_parse_args(
    $args,
    array(
        'kicker' => '',
        'title' => '',
        'intro' => '',
        'items' => array(),
        'header_preset' => 'page-section',
    )
);

$items = starter_normalize_component_items($args['items']);
$has_header = starter_component_header_has_content($args);
?>
<div class="section-offers content-stack content-stack--section">
    <?php if ($has_header) : ?>
        <?php
        get_template_part(
            'components/section-header',
            null,
            array(
                'preset' => (string) $args['header_preset'],
                'kicker' => (string) $args['kicker'],
                'title' => (string) $args['title'],
                'intro' => (string) $args['intro'],
                'attributes' => array(
                    'data-reveal' => 'true',
                ),
            )
        );
        ?>
    <?php endif; ?>

    <?php if (!empty($items)) : ?>
        <div class="section-offers__grid">
            <?php foreach ($items as $item) : ?>
                <?php
                $item = starter_normalize_component_card($item);

                if ($item['title'] === '') {
                    continue;
                }
                ?>
                <article class="<?php echo esc_attr(starter_get_panel_classes('soft', 'section-offer-card p-6')); ?>" data-reveal="true">
                    <div class="section-offer-card__header">
                        <?php if ($item['badge'] !== '') : ?>
                            <span class="<?php echo esc_attr(starter_get_badge_classes('accent-soft')); ?>">
                                <?php echo esc_html($item['badge']); ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="section-offer-card__title"><?php echo esc_html($item['title']); ?></h3>
                    </div>

                    <?php if ($item['meta'] !== '') : ?>
                        <p class="section-offer-card__meta"><?php echo esc_html($item['meta']); ?></p>
                    <?php endif; ?>

                    <?php if ($item['text'] !== '') : ?>
                        <p class="section-offer-card__text"><?php echo esc_html($item['text']); ?></p>
                    <?php endif; ?>

                    <?php if ($item['href'] !== '') : ?>
                        <?php
                        get_template_part(
                            'components/link-row',
                            null,
                            array(
                                'text' => $item['action_label'],
                                'href' => $item['href'],
                                'icon' => $item['icon'] !== '' ? $item['icon'] : 'arrow-right',
                            )
                        );
                        ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
