<?php
$args = wp_parse_args(
    $args,
    array(
        'items' => array(),
        'class' => '',
        'item_class' => '',
        'icon_class' => '',
        'label_class' => '',
    )
);

$items = is_array($args['items']) ? $args['items'] : array();
if (empty($items)) {
    return;
}
?>
<div class="<?php echo esc_attr(starter_merge_classes('ui-contact-list', (string) $args['class'])); ?>">
    <?php foreach ($items as $item) : ?>
        <?php
        if (!is_array($item)) {
            continue;
        }

        $item_label = trim((string) ($item['label'] ?? $item['text'] ?? ''));
        $item_href = trim((string) ($item['href'] ?? ''));
        $item_tag = $item_href !== '' ? 'a' : 'p';

        if ($item_label === '') {
            continue;
        }

        $icon_markup = trim((string) ($item['icon_markup'] ?? ''));
        if ($icon_markup === '' && trim((string) ($item['icon'] ?? '')) !== '') {
            $icon_markup = starter_get_icon_svg(
                sanitize_key((string) $item['icon']),
                array(
                    'class' => starter_merge_classes('ui-contact-item__glyph', (string) $args['icon_class']),
                    'size' => '1em',
                )
            );
        }

        $item_attributes = array(
            'class' => starter_merge_classes('ui-contact-item', (string) $args['item_class'], (string) ($item['class'] ?? '')),
        );

        if ($item_tag === 'a') {
            $item_attributes['href'] = $item_href;
        }
        ?>
        <<?php echo esc_attr($item_tag); ?> <?php echo starter_render_html_attributes($item_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <?php if ($icon_markup !== '') : ?>
                <span class="ui-contact-item__icon" aria-hidden="true">
                    <?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </span>
            <?php endif; ?>
            <span class="<?php echo esc_attr(starter_merge_classes('ui-contact-item__label', (string) $args['label_class'])); ?>">
                <?php echo esc_html($item_label); ?>
            </span>
        </<?php echo esc_attr($item_tag); ?>>
    <?php endforeach; ?>
</div>
