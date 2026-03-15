<?php
$raw_args = is_array($args) ? $args : array();
$preset = trim((string) ($raw_args['preset'] ?? ''));

$preset_map = array(
    'page-intro' => array(
        'header' => '',
        'kicker' => 'text-xs font-bold tracking-ui uppercase text-muted',
        'title' => 'text-title-page max-w-[14ch]',
        'intro' => 'text-body-intro leading-relaxed text-muted',
    ),
    'page-section' => array(
        'header' => '',
        'kicker' => 'text-xs font-bold tracking-ui uppercase text-muted',
        'title' => 'text-title-section max-w-[18ch]',
        'intro' => 'leading-relaxed text-muted',
    ),
    'empty-state' => array(
        'header' => 'items-center text-center',
        'kicker' => 'text-xs font-bold tracking-ui uppercase text-muted',
        'title' => 'text-title-section max-w-[18ch]',
        'intro' => 'leading-relaxed text-muted',
    ),
    'centered-display' => array(
        'header' => 'items-center text-center',
        'kicker' => 'text-xs font-bold tracking-ui uppercase text-muted',
        'title' => 'text-title-display max-w-none',
        'intro' => 'leading-relaxed text-muted',
    ),
    'scene-display' => array(
        'header' => '',
        'kicker' => 'text-xs font-bold tracking-ui uppercase text-muted',
        'title' => 'text-title-display max-w-none',
        'intro' => 'leading-relaxed text-muted',
    ),
    'sidebar-section' => array(
        'header' => '',
        'kicker' => 'text-xs font-bold tracking-ui uppercase text-muted',
        'title' => 'text-title-section max-w-[18ch]',
        'intro' => 'leading-relaxed text-muted',
    ),
);

$resolved = $preset_map[$preset] ?? array(
    'header' => '',
    'kicker' => 'text-xs font-bold tracking-ui uppercase text-muted',
    'title' => 'text-title-section max-w-[18ch]',
    'intro' => 'leading-relaxed text-muted',
);

$args = wp_parse_args($raw_args, array(
    'kicker' => '',
    'title' => '',
    'intro' => '',
    'title_tag' => 'h2',
    'class' => '',
    'title_class' => '',
    'intro_class' => '',
    'attributes' => array(),
));

$title_tag = strtolower(trim((string) $args['title_tag']));
if (!in_array($title_tag, array('h1', 'h2', 'h3'), true)) {
    $title_tag = 'h2';
}

$header_attributes = is_array($args['attributes']) ? $args['attributes'] : array();
$header_attributes['class'] = starter_merge_classes(
    'ui-section-header',
    $resolved['header'],
    (string) $args['class']
);

$title_classes = starter_merge_classes($resolved['title'], (string) $args['title_class']);
$intro_classes = starter_merge_classes($resolved['intro'], (string) $args['intro_class']);
?>
<header <?php echo starter_render_html_attributes($header_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php if (trim((string) $args['kicker']) !== '') : ?>
        <p class="<?php echo esc_attr(starter_merge_classes($resolved['kicker'])); ?>"><?php echo esc_html((string) $args['kicker']); ?></p>
    <?php endif; ?>

    <<?php echo esc_attr($title_tag); ?> class="<?php echo esc_attr($title_classes); ?>">
        <?php echo esc_html((string) $args['title']); ?>
    </<?php echo esc_attr($title_tag); ?>>

    <?php if (trim((string) $args['intro']) !== '') : ?>
        <p class="<?php echo esc_attr($intro_classes); ?>">
            <?php echo esc_html((string) $args['intro']); ?>
        </p>
    <?php endif; ?>
</header>
