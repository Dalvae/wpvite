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

$args = wp_parse_args($args, [
    'text' => 'Button',
    'href' => '#',
    'target' => '_self',
    'class' => '',
    'variant' => 'primary',
    'size' => 'md',
    'radius' => 'md',
]);

$allowed_variants = ['primary', 'secondary', 'tertiary', 'warning', 'success', 'danger', 'ghost'];
$allowed_sizes = ['sm', 'md', 'lg'];
$allowed_radius = ['sm', 'md', 'lg', 'pill'];

$variant = in_array($args['variant'], $allowed_variants, true) ? $args['variant'] : 'primary';
$size = in_array($args['size'], $allowed_sizes, true) ? $args['size'] : 'md';
$radius = in_array($args['radius'], $allowed_radius, true) ? $args['radius'] : 'md';

$classes = trim(implode(' ', [
    'btn',
    "btn--{$variant}",
    "btn--{$size}",
    "btn--radius-{$radius}",
    $args['class'],
]));

$rel = '_blank' === $args['target'] ? 'noopener noreferrer' : '';

?>
<a href="<?php echo esc_url($args['href']); ?>" class="<?php echo esc_attr($classes); ?>" target="<?php echo esc_attr($args['target']); ?>" rel="<?php echo esc_attr($rel); ?>">
    <?php echo esc_html($args['text']); ?>
</a>
