<?php
$args = wp_parse_args(
    $args,
    array(
        'kicker' => '',
        'title' => '',
        'intro' => '',
        'items' => array(),
        'header_preset' => 'centered-display',
    )
);

$items = array_values(array_filter((array) $args['items'], 'is_array'));
?>
<div class="section-final-cta content-stack content-stack--section">
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

    <?php if (!empty($items)) : ?>
        <div class="section-final-cta__grid">
            <?php foreach ($items as $item) : ?>
                <?php
                $title = trim((string) ($item['title'] ?? ''));
                $text = trim((string) ($item['text'] ?? $item['description'] ?? ''));
                $kicker = trim((string) ($item['kicker'] ?? ''));
                $href = trim((string) ($item['href'] ?? $item['url'] ?? ''));
                $action = trim((string) ($item['action_label'] ?? 'Explore'));
                $variant = trim((string) ($item['variant'] ?? 'primary'));
                if ($title === '' || $href === '') {
                    continue;
                }
                ?>
                <article class="<?php echo esc_attr(starter_get_panel_classes('soft', 'section-final-cta__card p-6')); ?>" data-reveal="true">
                    <?php if ($kicker !== '') : ?>
                        <p class="section-final-cta__kicker"><?php echo esc_html($kicker); ?></p>
                    <?php endif; ?>
                    <h3 class="section-final-cta__title"><?php echo esc_html($title); ?></h3>
                    <?php if ($text !== '') : ?>
                        <p class="section-final-cta__text"><?php echo esc_html($text); ?></p>
                    <?php endif; ?>
                    <?php
                    get_template_part(
                        'components/button',
                        null,
                        array(
                            'text' => $action,
                            'href' => $href,
                            'variant' => $variant,
                            'icon' => $variant === 'secondary' ? 'external-link' : 'arrow-right',
                        )
                    );
                    ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
