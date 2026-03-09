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
<div class="section-proof content-stack content-stack--section">
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
        <div class="section-proof__grid">
            <?php foreach ($items as $item) : ?>
                <?php
                $card_tag = !empty($item['href']) ? 'a' : 'article';
                $card_attributes = array(
                    'class' => starter_get_panel_classes('soft', starter_merge_classes('section-proof-card p-6', !empty($item['href']) ? 'section-proof-card--link' : '')),
                );

                if (!empty($item['href'])) {
                    $card_attributes['href'] = (string) $item['href'];
                }

                $title = trim((string) ($item['title'] ?? ''));
                $quote = trim((string) ($item['quote'] ?? ''));
                $text = trim((string) ($item['text'] ?? $item['description'] ?? ''));
                $author = trim((string) ($item['author'] ?? ''));
                $role = trim((string) ($item['role'] ?? ''));
                $source = trim((string) ($item['source_label'] ?? $item['source'] ?? ''));
                ?>
                <<?php echo esc_attr($card_tag); ?> <?php echo starter_render_html_attributes($card_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-reveal="true">
                    <?php if ($title !== '') : ?>
                        <h3 class="section-proof-card__title"><?php echo esc_html($title); ?></h3>
                    <?php endif; ?>

                    <?php if ($quote !== '') : ?>
                        <blockquote class="section-proof-card__quote">
                            <?php echo esc_html($quote); ?>
                        </blockquote>
                    <?php elseif ($text !== '') : ?>
                        <p class="section-proof-card__text"><?php echo esc_html($text); ?></p>
                    <?php endif; ?>

                    <?php if ($author !== '' || $role !== '' || $source !== '') : ?>
                        <footer class="section-proof-card__footer">
                            <?php if ($author !== '') : ?>
                                <p class="section-proof-card__author"><?php echo esc_html($author); ?></p>
                            <?php endif; ?>
                            <?php if ($role !== '') : ?>
                                <p class="section-proof-card__role"><?php echo esc_html($role); ?></p>
                            <?php endif; ?>
                            <?php if ($source !== '') : ?>
                                <p class="section-proof-card__source"><?php echo esc_html($source); ?></p>
                            <?php endif; ?>
                        </footer>
                    <?php endif; ?>
                </<?php echo esc_attr($card_tag); ?>>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
