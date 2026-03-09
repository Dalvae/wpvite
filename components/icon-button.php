<?php
$args = wp_parse_args(
    $args,
    array(
        'tag' => '',
        'href' => '',
        'type' => 'button',
        'target' => '',
        'rel' => '',
        'aria_label' => '',
        'class' => '',
        'variant' => 'surface',
        'icon' => '',
        'icon_markup' => '',
        'icon_size' => '1.125em',
        'icon_class' => '',
        'attributes' => array(),
    )
);

$tag = trim((string) $args['tag']);
if ($tag === '') {
    $tag = trim((string) $args['href']) !== '' ? 'a' : 'button';
}

$tag = strtolower($tag);
if (!in_array($tag, array('a', 'button'), true)) {
    $tag = 'button';
}

$attributes = is_array($args['attributes']) ? $args['attributes'] : array();
$attributes['class'] = starter_get_icon_button_classes((string) $args['variant'], (string) $args['class']);

if (trim((string) $args['aria_label']) !== '') {
    $attributes['aria-label'] = (string) $args['aria_label'];
}

if ($tag === 'a') {
    $attributes['href'] = (string) $args['href'];
    if (trim((string) $args['target']) !== '') {
        $attributes['target'] = (string) $args['target'];
    }

    $link_rel = trim((string) $args['rel']);
    if (($attributes['target'] ?? '') === '_blank' && $link_rel === '') {
        $link_rel = 'noopener noreferrer';
    }

    if ($link_rel !== '') {
        $attributes['rel'] = $link_rel;
    }
} else {
    $attributes['type'] = trim((string) $args['type']) !== '' ? (string) $args['type'] : 'button';
}

$icon_markup = trim((string) $args['icon_markup']);
if ($icon_markup === '' && trim((string) $args['icon']) !== '') {
    $icon_markup = starter_get_icon_svg(
        sanitize_key((string) $args['icon']),
        array(
            'class' => starter_merge_classes('icon-button__icon', (string) $args['icon_class']),
            'size' => (string) $args['icon_size'],
        )
    );
}

if ($icon_markup === '') {
    return;
}
?>
<<?php echo esc_attr($tag); ?> <?php echo starter_render_html_attributes($attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</<?php echo esc_attr($tag); ?>>
