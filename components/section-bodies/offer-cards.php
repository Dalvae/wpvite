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

$items = array_values(array_filter((array) $args['items'], 'is_array'));
$has_header = trim((string) $args['kicker']) !== '' || trim((string) $args['title']) !== '' || trim((string) $args['intro']) !== '';
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
                $item_title = trim((string) ($item['title'] ?? ''));
                $item_text = trim((string) ($item['text'] ?? $item['description'] ?? ''));
                $item_meta = trim((string) ($item['meta'] ?? $item['subtitle'] ?? ''));
                $item_badge = trim((string) ($item['badge'] ?? ''));
                $item_href = trim((string) ($item['href'] ?? $item['url'] ?? ''));
                $item_action = trim((string) ($item['action_label'] ?? 'Learn more'));
                $item_icon = trim((string) ($item['icon'] ?? ''));

                if ($item_title === '') {
                    continue;
                }
                ?>
                <article class="<?php echo esc_attr(starter_get_panel_classes('soft', 'section-offer-card p-6')); ?>" data-reveal="true">
                    <div class="section-offer-card__header">
                        <?php if ($item_badge !== '') : ?>
                            <span class="<?php echo esc_attr(starter_get_badge_classes('accent-soft')); ?>">
                                <?php echo esc_html($item_badge); ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="section-offer-card__title"><?php echo esc_html($item_title); ?></h3>
                    </div>

                    <?php if ($item_meta !== '') : ?>
                        <p class="section-offer-card__meta"><?php echo esc_html($item_meta); ?></p>
                    <?php endif; ?>

                    <?php if ($item_text !== '') : ?>
                        <p class="section-offer-card__text"><?php echo esc_html($item_text); ?></p>
                    <?php endif; ?>

                    <?php if ($item_href !== '') : ?>
                        <?php
                        get_template_part(
                            'components/link-row',
                            null,
                            array(
                                'text' => $item_action,
                                'href' => $item_href,
                                'icon' => $item_icon !== '' ? $item_icon : 'arrow-right',
                            )
                        );
                        ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
