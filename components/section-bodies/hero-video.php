<?php
$args = wp_parse_args(
    $args,
    array(
        'kicker' => '',
        'title' => '',
        'title_accent' => '',
        'intro' => '',
        'title_tag' => 'h1',
        'actions' => array(),
        'video_url' => '',
        'video_fallback_image' => '',
        'video_fallback_size' => 'large',
        'image_id' => 0,
        'video_id' => 0,
        'mobile_image_id' => 0,
        'mobile_image_size' => 'medium_large',
        'ticker_items' => array(),
        'proximity_panel' => array(),
    )
);

if (trim((string) $args['video_url']) === '' && (int) $args['video_id'] > 0) {
    $resolved = (string) wp_get_attachment_url((int) $args['video_id']);
    if ($resolved !== '') {
        $args['video_url'] = $resolved;
    }
}

if (trim((string) $args['video_fallback_image']) === '' && (int) $args['image_id'] > 0) {
    $resolved = (string) wp_get_attachment_image_url((int) $args['image_id'], (string) $args['video_fallback_size']);
    if ($resolved !== '') {
        $args['video_fallback_image'] = $resolved;
    }
}

$mobile_image_url = '';
if ((int) $args['mobile_image_id'] > 0) {
    $mobile_image_url = (string) wp_get_attachment_image_url((int) $args['mobile_image_id'], (string) $args['mobile_image_size']);
}

$actions = starter_normalize_component_actions($args['actions']);
$allowed_title_tags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
$title_tag = strtolower((string) $args['title_tag']);
if (!in_array($title_tag, $allowed_title_tags, true)) {
    $title_tag = 'h1';
}
$ticker_items = array_values(array_filter((array) $args['ticker_items'], 'is_string'));
$proximity = is_array($args['proximity_panel']) ? $args['proximity_panel'] : array();
$proximity_items = array_values(array_filter((array) ($proximity['items'] ?? array()), 'is_string'));
$has_video = trim((string) $args['video_url']) !== '';
$has_fallback = trim((string) $args['video_fallback_image']) !== '';
$has_mobile = $mobile_image_url !== '';
$title_accent = trim((string) $args['title_accent']);
$video_mime = 'video/mp4';
if ((int) $args['video_id'] > 0) {
    $attachment_mime = (string) get_post_mime_type((int) $args['video_id']);
    if ($attachment_mime !== '') {
        $video_mime = $attachment_mime;
    }
}
?>
<div class="section-video-hero">
    <?php if ($has_video || $has_fallback || $has_mobile) : ?>
        <div class="section-video-hero__backdrop" aria-hidden="true">
            <?php if ($has_video) : ?>
                <video class="section-video-hero__video hidden md:block" autoplay muted loop playsinline preload="metadata" <?php if ($has_fallback) : ?>poster="<?php echo esc_url((string) $args['video_fallback_image']); ?>"<?php endif; ?>>
                    <source src="<?php echo esc_url((string) $args['video_url']); ?>" type="<?php echo esc_attr($video_mime); ?>" media="(min-width: 768px)">
                </video>
            <?php endif; ?>
            <?php if ($has_mobile) : ?>
                <img class="section-video-hero__still md:hidden" src="<?php echo esc_url($mobile_image_url); ?>" alt="" loading="eager" fetchpriority="high" decoding="async">
            <?php elseif ($has_fallback) : ?>
                <img class="section-video-hero__still<?php echo $has_video ? ' md:hidden' : ''; ?>" src="<?php echo esc_url((string) $args['video_fallback_image']); ?>" alt="" loading="eager" fetchpriority="high" decoding="async">
            <?php endif; ?>
            <div class="section-video-hero__scrim"></div>
        </div>
    <?php endif; ?>

    <div class="section-video-hero__shell brand-shell">
        <div class="section-video-hero__grid">
            <div class="section-video-hero__copy content-stack content-stack--copy" data-reveal="true">
                <?php if (trim((string) $args['kicker']) !== '') : ?>
                    <p class="section-video-hero__kicker"><?php echo esc_html((string) $args['kicker']); ?></p>
                <?php endif; ?>
                <<?php echo esc_attr($title_tag); ?> class="section-video-hero__title">
                    <?php echo esc_html((string) $args['title']); ?><?php if ($title_accent !== '') : ?> <em><?php echo esc_html($title_accent); ?></em><?php endif; ?>
                </<?php echo esc_attr($title_tag); ?>>
                <?php if (trim((string) $args['intro']) !== '') : ?>
                    <p class="section-video-hero__intro"><?php echo esc_html((string) $args['intro']); ?></p>
                <?php endif; ?>
                <?php if (!empty($actions)) : ?>
                    <div class="section-video-hero__actions">
                        <?php foreach ($actions as $action) : ?>
                            <?php get_template_part('components/button', null, $action); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($proximity_items)) : ?>
                <aside class="section-video-hero__panel" aria-label="<?php echo esc_attr((string) ($proximity['title'] ?? __('Highlights', 'wpvite'))); ?>" data-reveal="true">
                    <?php if (!empty($proximity['title'])) : ?>
                        <h2 class="section-video-hero__panel-title"><?php echo esc_html((string) $proximity['title']); ?></h2>
                    <?php endif; ?>
                    <ul class="section-video-hero__panel-list">
                        <?php foreach ($proximity_items as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($ticker_items)) : ?>
        <div class="section-video-hero__ticker" aria-label="<?php esc_attr_e('Highlights', 'wpvite'); ?>">
            <?php foreach ($ticker_items as $item) : ?>
                <span><?php echo esc_html($item); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
