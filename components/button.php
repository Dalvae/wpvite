<?php

// get_template_part(slug: 'components/button', args: [
//   'text' => 'My Button',
//   'href' => 'https://example.com',
//   'target' => '_blank',
//   'class' => '',
//   'variant' => 'primary',
//   'size' => 'md',
//   'radius' => 'md',
// ]);

$args = wp_parse_args(
    $args,
    array(
        'text' => 'Button',
        'href' => '',
        'tag' => '',
        'type' => 'button',
        'target' => '',
        'rel' => '',
        'aria_label' => '',
        'class' => '',
        'variant' => 'primary',
        'size' => 'md',
        'radius' => 'md',
        'icon' => '',
        'icon_markup' => '',
        'icon_class' => '',
        'icon_position' => 'after',
        'attributes' => array(),
    )
);

$tag = trim((string) $args['tag']);
if ($tag === '') {
    $tag = trim((string) $args['href']) !== '' ? 'a' : 'button';
}

$tag = strtolower($tag);
if (!in_array($tag, array('a', 'button'), true)) {
    $tag = 'a';
}

$label = trim((string) $args['text']);
$icon_markup = trim((string) $args['icon_markup']);
if ($icon_markup === '' && trim((string) $args['icon']) !== '') {
    $icon_markup = starter_icon(
        (string) $args['icon'],
        array(
            'class' => starter_merge_classes('btn__icon', (string) $args['icon_class']),
            'size' => '1em',
        )
    );
}

if ($label === '' && $icon_markup === '') {
    return;
}

$attributes = is_array($args['attributes']) ? $args['attributes'] : array();
$attributes['class'] = starter_get_button_classes((string) $args['variant'], (string) $args['size'], (string) $args['radius'], (string) $args['class']);

if (trim((string) $args['aria_label']) !== '') {
    $attributes['aria-label'] = (string) $args['aria_label'];
}

if ($tag === 'a') {
    $attributes['href'] = trim((string) $args['href']) !== '' ? (string) $args['href'] : '#';
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
?>
<<?php echo esc_attr($tag); ?> <?php echo starter_render_html_attributes($attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php if ($icon_markup !== '' && (string) $args['icon_position'] === 'before') : ?>
        <?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php endif; ?>

    <?php if ($label !== '') : ?>
        <span><?php echo esc_html($label); ?></span>
    <?php endif; ?>

    <?php if ($icon_markup !== '' && (string) $args['icon_position'] !== 'before') : ?>
        <?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php endif; ?>
</<?php echo esc_attr($tag); ?>>
