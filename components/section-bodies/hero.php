<?php
$args = wp_parse_args(
    $args,
    array(
        'kicker' => '',
        'title' => '',
        'intro' => '',
        'title_tag' => 'h1',
        'actions' => array(),
        'header_preset' => 'page-intro',
        'media_html' => '',
        'media_image_url' => '',
        'media_image_alt' => '',
        'media_surface_class' => '',
    )
);

$actions = array_values(array_filter((array) $args['actions'], 'is_array'));
$has_media = trim((string) $args['media_html']) !== '' || trim((string) $args['media_image_url']) !== '';
?>
<div class="section-hero__copy content-stack content-stack--copy" data-reveal="true">
    <?php
    get_template_part(
        'components/section-header',
        null,
        array(
            'preset' => (string) $args['header_preset'],
            'kicker' => (string) $args['kicker'],
            'title' => (string) $args['title'],
            'intro' => (string) $args['intro'],
            'title_tag' => (string) $args['title_tag'],
            'class' => 'section-hero__header',
        )
    );
    ?>

    <?php if (!empty($actions)) : ?>
        <div class="section-hero__actions">
            <?php foreach ($actions as $action) : ?>
                <?php
                $action_text = trim((string) ($action['text'] ?? $action['label'] ?? ''));
                $action_href = trim((string) ($action['href'] ?? $action['url'] ?? ''));
                if ($action_text === '' || $action_href === '') {
                    continue;
                }

                get_template_part(
                    'components/button',
                    null,
                    array(
                        'text' => $action_text,
                        'href' => $action_href,
                        'variant' => (string) ($action['variant'] ?? 'primary'),
                        'icon' => (string) ($action['icon'] ?? ''),
                        'icon_position' => (string) ($action['icon_position'] ?? 'after'),
                        'target' => (string) ($action['target'] ?? ''),
                        'rel' => (string) ($action['rel'] ?? ''),
                    )
                );
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($has_media) : ?>
    <div class="section-hero__media" data-reveal="true">
        <?php if (trim((string) $args['media_image_url']) !== '') : ?>
            <figure class="<?php echo esc_attr(starter_get_panel_classes('soft', starter_merge_classes('section-hero__media-card p-3 sm:p-4', (string) $args['media_surface_class']))); ?>">
                <img
                    class="section-hero__image"
                    src="<?php echo esc_url((string) $args['media_image_url']); ?>"
                    alt="<?php echo esc_attr((string) $args['media_image_alt']); ?>"
                    loading="lazy">
            </figure>
        <?php elseif (trim((string) $args['media_html']) !== '') : ?>
            <div class="<?php echo esc_attr(starter_get_panel_classes('soft', starter_merge_classes('section-hero__media-card p-4', (string) $args['media_surface_class']))); ?>">
                <?php echo wp_kses_post((string) $args['media_html']); ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
