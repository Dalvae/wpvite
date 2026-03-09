<?php
$raw_args = is_array($args) ? $args : array();
$preset = trim((string) ($raw_args['preset'] ?? ''));
if ($preset !== '' && function_exists('starter_get_section_header_preset_args')) {
    $raw_args = starter_get_section_header_preset_args($preset, $raw_args);
}

$args = wp_parse_args(
    $raw_args,
    array(
        'preset' => '',
        'kicker' => '',
        'title' => '',
        'intro' => '',
        'title_tag' => 'h2',
        'layout' => '',
        'title_family' => '',
        'title_tone' => '',
        'intro_family' => '',
        'intro_tone' => '',
        'class' => '',
        'title_class' => '',
        'intro_class' => '',
        'attributes' => array(),
    )
);

$title_tag = strtolower(trim((string) $args['title_tag']));
if (!in_array($title_tag, array('h1', 'h2', 'h3'), true)) {
    $title_tag = 'h2';
}

$resolved_layout = trim((string) $args['layout']);
if ($resolved_layout === '') {
    $resolved_layout = 'default';
}

$resolved_title_family = trim((string) $args['title_family']) !== '' ? trim((string) $args['title_family']) : 'section';
$resolved_title_tone = trim((string) $args['title_tone']) !== '' ? trim((string) $args['title_tone']) : 'default';
$resolved_intro_family = trim((string) $args['intro_family']) !== '' ? trim((string) $args['intro_family']) : 'section';
$resolved_intro_tone = trim((string) $args['intro_tone']) !== '' ? trim((string) $args['intro_tone']) : 'default';

$header_attributes = is_array($args['attributes']) ? $args['attributes'] : array();
$header_attributes['class'] = starter_merge_classes(
    'ui-section-header',
    starter_get_section_header_classes($resolved_layout),
    (string) $args['class']
);

$title_classes = starter_get_section_title_classes($resolved_title_family, $resolved_title_tone, (string) $args['title_class']);
$intro_classes = starter_get_section_intro_classes($resolved_intro_family, $resolved_intro_tone, (string) $args['intro_class']);
?>
<header <?php echo starter_render_html_attributes($header_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php if (trim((string) $args['kicker']) !== '') : ?>
        <p class="ui-section-kicker"><?php echo esc_html((string) $args['kicker']); ?></p>
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
