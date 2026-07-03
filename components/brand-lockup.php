<?php
$custom_logo_id = (int) get_theme_mod('custom_logo');
$default_logo_uri = $custom_logo_id > 0 ? (string) wp_get_attachment_image_url($custom_logo_id, 'full') : '';
$default_logo_width = 0;
$default_logo_height = 0;
if ($custom_logo_id > 0) {
    $logo_meta = wp_get_attachment_metadata($custom_logo_id);
    if (is_array($logo_meta)) {
        $default_logo_width = (int) ($logo_meta['width'] ?? 0);
        $default_logo_height = (int) ($logo_meta['height'] ?? 0);
    }
}

$args = wp_parse_args(
    $args,
    array(
        'class' => '',
        'link_class' => '',
        'logo_class' => '',
        'wordmark_class' => '',
        'tagline_class' => '',
        'show_tagline' => false,
        'tagline' => function_exists('starter_get_site_tagline') ? starter_get_site_tagline() : (string) get_bloginfo('description'),
        'brand_name' => function_exists('starter_get_site_name') ? starter_get_site_name() : (string) get_bloginfo('name'),
        'logo_uri' => $default_logo_uri,
        'loading' => 'eager',
        'decoding' => 'async',
        'width' => $default_logo_width,
        'height' => $default_logo_height,
    )
);

$brand_name = trim((string) $args['brand_name']);
$brand_tagline = trim((string) $args['tagline']);
$logo_uri = trim((string) $args['logo_uri']);
?>
<div class="<?php echo esc_attr(starter_merge_classes('ui-brand-lockup', (string) $args['class'])); ?>">
    <?php if ($logo_uri !== '') : ?>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="<?php echo esc_attr(starter_merge_classes('ui-brand-lockup__logo-link', (string) $args['link_class'])); ?>" aria-label="<?php echo esc_attr($brand_name); ?>">
            <img
                src="<?php echo esc_url($logo_uri); ?>"
                alt="<?php echo esc_attr($brand_name); ?>"
                class="<?php echo esc_attr(starter_merge_classes('ui-brand-lockup__logo-image', (string) $args['logo_class'])); ?>"
                loading="<?php echo esc_attr((string) $args['loading']); ?>"
                decoding="<?php echo esc_attr((string) $args['decoding']); ?>"
                <?php if ((int) $args['width'] > 0) : ?>width="<?php echo esc_attr((string) (int) $args['width']); ?>"<?php endif; ?>
                <?php if ((int) $args['height'] > 0) : ?>height="<?php echo esc_attr((string) (int) $args['height']); ?>"<?php endif; ?>>
        </a>
    <?php else : ?>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="<?php echo esc_attr(starter_merge_classes('ui-brand-lockup__wordmark', (string) $args['link_class'], (string) $args['wordmark_class'])); ?>">
            <?php echo esc_html($brand_name); ?>
        </a>
    <?php endif; ?>

    <?php if (!empty($args['show_tagline']) && $brand_tagline !== '') : ?>
        <p class="<?php echo esc_attr(starter_merge_classes('ui-brand-lockup__tagline', (string) $args['tagline_class'])); ?>">
            <?php echo esc_html($brand_tagline); ?>
        </p>
    <?php endif; ?>
</div>
