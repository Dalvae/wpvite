<?php
$args = wp_parse_args(
    $args,
    array(
        'section_tag' => 'section',
        'surface' => 'plain',
        'surface_variant' => '',
        'spacing' => 'scene',
        'spacing_variant' => '',
        'divider_variant' => 'none',
        'layout_variant' => '',
        'class' => '',
        'attributes' => array(),
        'section_id' => '',
        'shell_variant' => 'brand',
        'shell_class' => '',
        'content_class' => '',
        'content_attributes' => array(),
        'body_template' => '',
        'body_args' => array(),
    )
);

$section_tag = strtolower(trim((string) $args['section_tag']));
if (!in_array($section_tag, array('section', 'div'), true)) {
    $section_tag = 'section';
}

$resolve_variant = static function ($preferred_value, $legacy_value, string $default): string {
    $preferred = trim((string) $preferred_value);
    if ($preferred !== '') {
        return $preferred;
    }

    $legacy = trim((string) $legacy_value);
    if ($legacy !== '') {
        return $legacy;
    }

    return $default;
};

$section_surface_variant = $resolve_variant($args['surface_variant'], $args['surface'], 'plain');
$section_spacing_variant = $resolve_variant($args['spacing_variant'], $args['spacing'], 'scene');
$section_divider_variant = trim((string) $args['divider_variant']);
$section_layout_variant = trim((string) $args['layout_variant']);
$section_id = sanitize_title((string) $args['section_id']);

$section_attributes = is_array($args['attributes']) ? $args['attributes'] : array();
$section_attributes['class'] = starter_get_section_scene_classes(
    $section_surface_variant,
    $section_spacing_variant,
    (string) $args['class'],
    $section_divider_variant
);
if ($section_id !== '') {
    $section_attributes['id'] = $section_id;
}

$shell_classes = starter_get_scene_shell_classes((string) $args['shell_variant'], (string) $args['shell_class']);
$body_template = trim((string) $args['body_template']);
$body_args = is_array($args['body_args']) ? $args['body_args'] : array();
$content_attributes = is_array($args['content_attributes']) ? $args['content_attributes'] : array();
$content_class = trim((string) $args['content_class']);
$layout_class = '';
if ($section_layout_variant !== '' && starter_is_scene_layout_variant($section_layout_variant)) {
    $layout_class = starter_get_scene_layout_classes($section_layout_variant);
}

$has_content_wrapper = $layout_class !== '' || $content_class !== '' || !empty($content_attributes);
if ($has_content_wrapper) {
    $content_attributes['class'] = starter_merge_classes($layout_class, $content_class);
}
?>
<<?php echo esc_attr($section_tag); ?> <?php echo starter_render_html_attributes($section_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="<?php echo esc_attr($shell_classes); ?>">
        <?php if ($has_content_wrapper) : ?>
            <div <?php echo starter_render_html_attributes($content_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <?php if ($body_template !== '') : ?>
                    <?php get_template_part($body_template, null, $body_args); ?>
                <?php endif; ?>
            </div>
        <?php elseif ($body_template !== '') : ?>
            <?php get_template_part($body_template, null, $body_args); ?>
        <?php endif; ?>
    </div>
</<?php echo esc_attr($section_tag); ?>>
