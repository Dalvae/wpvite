<?php
$args = wp_parse_args(
    $args,
    array(
        'text' => '',
        'href' => '',
        'class' => '',
        'text_class' => '',
        'icon' => 'arrow-right',
        'icon_markup' => '',
        'icon_class' => '',
        'icon_size' => '1em',
        'target' => '',
        'rel' => '',
        'attributes' => array(),
    )
);

$link_text = trim((string) $args['text']);
$link_href = trim((string) $args['href']);
if ($link_text === '' || $link_href === '') {
    return;
}

$link_target = trim((string) $args['target']);
$link_rel = trim((string) $args['rel']);
if ($link_target === '_blank' && $link_rel === '') {
    $link_rel = 'noopener noreferrer';
}

$link_attributes = is_array($args['attributes']) ? $args['attributes'] : array();
$link_attributes['href'] = $link_href;
$link_attributes['class'] = starter_merge_classes('ui-link-row', (string) $args['class']);

if ($link_target !== '') {
    $link_attributes['target'] = $link_target;
}

if ($link_rel !== '') {
    $link_attributes['rel'] = $link_rel;
}

$icon_markup = trim((string) $args['icon_markup']);
if ($icon_markup === '' && trim((string) $args['icon']) !== '') {
    $icon_markup = starter_get_icon_svg(
        sanitize_key((string) $args['icon']),
        array(
            'class' => starter_merge_classes('ui-link-row__icon', (string) $args['icon_class']),
            'size' => (string) $args['icon_size'],
        )
    );
}
?>
<a <?php echo starter_render_html_attributes($link_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <span class="<?php echo esc_attr(starter_merge_classes('ui-link-row__text', (string) $args['text_class'])); ?>">
        <?php echo esc_html($link_text); ?>
    </span>
    <?php if ($icon_markup !== '') : ?>
        <?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php endif; ?>
</a>
