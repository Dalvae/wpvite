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
        'media_loading' => 'eager',
        'media_fetchpriority' => 'high',
        'media_decoding' => 'async',
    )
);

$actions = starter_normalize_component_actions($args['actions']);
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
                get_template_part(
                    'components/button',
                    null,
                    array(
                        'text' => $action['text'],
                        'href' => $action['href'],
                        'variant' => $action['variant'],
                        'icon' => $action['icon'],
                        'icon_position' => $action['icon_position'],
                        'target' => $action['target'],
                        'rel' => $action['rel'],
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
                    loading="<?php echo esc_attr((string) $args['media_loading']); ?>"
                    fetchpriority="<?php echo esc_attr((string) $args['media_fetchpriority']); ?>"
                    decoding="<?php echo esc_attr((string) $args['media_decoding']); ?>">
            </figure>
        <?php elseif (trim((string) $args['media_html']) !== '') : ?>
            <div class="<?php echo esc_attr(starter_get_panel_classes('soft', starter_merge_classes('section-hero__media-card p-4', (string) $args['media_surface_class']))); ?>">
                <?php echo wp_kses_post((string) $args['media_html']); ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
