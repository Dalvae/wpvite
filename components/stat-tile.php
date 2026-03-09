<?php
$args = wp_parse_args(
    $args,
    array(
        'tag' => 'article',
        'class' => '',
        'value' => '',
        'value_parts' => array(),
        'value_tag' => 'span',
        'value_class' => '',
        'value_aria_label' => '',
        'number_part_class' => '',
        'text_part_class' => '',
        'label' => '',
        'label_tag' => 'span',
        'label_class' => '',
        'attributes' => array(),
    )
);

$tag = strtolower(trim((string) $args['tag']));
if (!in_array($tag, array('article', 'div', 'li'), true)) {
    $tag = 'article';
}

$value_tag = strtolower(trim((string) $args['value_tag']));
if (!in_array($value_tag, array('span', 'p', 'div'), true)) {
    $value_tag = 'span';
}

$label_tag = strtolower(trim((string) $args['label_tag']));
if (!in_array($label_tag, array('span', 'p', 'div'), true)) {
    $label_tag = 'span';
}

$value = trim((string) $args['value']);
$label = trim((string) $args['label']);
$value_parts = is_array($args['value_parts']) ? $args['value_parts'] : array();

if ($value === '' && empty($value_parts)) {
    return;
}

$tile_attributes = is_array($args['attributes']) ? $args['attributes'] : array();
$tile_attributes['class'] = starter_merge_classes('stat-tile', (string) $args['class']);

$value_attributes = array(
    'class' => starter_merge_classes('stat-tile__value', (string) $args['value_class']),
);

if (trim((string) $args['value_aria_label']) !== '') {
    $value_attributes['aria-label'] = (string) $args['value_aria_label'];
}
?>
<<?php echo esc_attr($tag); ?> <?php echo starter_render_html_attributes($tile_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <<?php echo esc_attr($value_tag); ?> <?php echo starter_render_html_attributes($value_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <?php if (!empty($value_parts)) : ?>
            <?php foreach ($value_parts as $part) : ?>
                <?php
                if (!is_array($part)) {
                    continue;
                }

                $part_type = isset($part['type']) ? (string) $part['type'] : 'text';
                $part_raw = trim((string) ($part['raw'] ?? ''));
                if ($part_raw === '') {
                    continue;
                }

                $part_class = $part_type === 'number'
                    ? starter_merge_classes('stat-tile__number', (string) $args['number_part_class'])
                    : starter_merge_classes('stat-tile__text', (string) $args['text_part_class']);

                $part_attributes = array(
                    'aria-hidden' => 'true',
                    'class' => $part_class,
                );

                if ($part_type === 'number') {
                    $part_attributes['data-count-up'] = 'true';
                    $part_attributes['data-count-up-decimals'] = (string) ((int) ($part['decimals'] ?? 0));
                    $part_attributes['data-count-up-value'] = (string) ($part['value'] ?? $part_raw);
                }
                ?>
                <span <?php echo starter_render_html_attributes($part_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html($part_raw); ?></span>
            <?php endforeach; ?>
        <?php else : ?>
            <?php echo esc_html($value); ?>
        <?php endif; ?>
    </<?php echo esc_attr($value_tag); ?>>

    <?php if ($label !== '') : ?>
        <<?php echo esc_attr($label_tag); ?> class="<?php echo esc_attr(starter_merge_classes('stat-tile__label', (string) $args['label_class'])); ?>">
            <?php echo esc_html($label); ?>
        </<?php echo esc_attr($label_tag); ?>>
    <?php endif; ?>
</<?php echo esc_attr($tag); ?>>
