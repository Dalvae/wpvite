<?php
$args = wp_parse_args(
    $args,
    array(
        'post_id' => 0,
        'title' => '',
        'url' => '',
        'summary' => '',
        'meta' => '',
        'action_label' => '',
        'image_url' => '',
        'image_alt' => '',
        'title_tag' => 'h2',
        'class' => '',
    )
);

$title_tag = strtolower(trim((string) $args['title_tag']));
if (!in_array($title_tag, array('h2', 'h3'), true)) {
    $title_tag = 'h2';
}

$card_args = starter_get_summary_card_component_args(
    array(
        'post_id' => (int) $args['post_id'],
        'title' => (string) $args['title'],
        'url' => (string) $args['url'],
        'summary' => (string) $args['summary'],
        'meta' => (string) $args['meta'],
        'action_label' => (string) $args['action_label'],
        'image_url' => (string) $args['image_url'],
        'image_alt' => (string) $args['image_alt'],
        'title_tag' => $title_tag,
        'class' => (string) $args['class'],
    )
);

$post_id = (int) $card_args['post_id'];
$card_image_url = trim((string) ($card_args['image_url'] ?? ''));
$card_image_alt = trim((string) ($card_args['image_alt'] ?? ''));

if ($post_id > 0 && $card_image_url === '' && has_post_thumbnail($post_id)) {
    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'large');
    if (is_string($thumbnail_url) && $thumbnail_url !== '') {
        $card_image_url = $thumbnail_url;
    }

    if ($card_image_alt === '') {
        $thumbnail_alt = get_post_meta((int) get_post_thumbnail_id($post_id), '_wp_attachment_image_alt', true);
        if (is_string($thumbnail_alt) && trim($thumbnail_alt) !== '') {
            $card_image_alt = trim($thumbnail_alt);
        }
    }
}

if ($card_image_alt === '') {
    $card_image_alt = (string) $card_args['title'];
}

$card_classes = starter_get_page_surface_classes('summary-card', (string) $card_args['class']);
?>
<?php if ($post_id > 0) : ?>
    <article id="post-<?php echo esc_attr((string) $post_id); ?>" <?php post_class($card_classes, $post_id); ?> data-reveal="true">
<?php else : ?>
    <article class="<?php echo esc_attr($card_classes); ?>" data-reveal="true">
<?php endif; ?>
    <?php if ($card_image_url !== '') : ?>
        <figure class="content-card__media">
            <img
                src="<?php echo esc_url($card_image_url); ?>"
                alt="<?php echo esc_attr($card_image_alt); ?>"
                class="content-card__image"
                loading="lazy">
        </figure>
    <?php endif; ?>

    <div class="<?php echo esc_attr(starter_get_content_stack_classes('body')); ?>">
        <?php
        get_template_part(
            'components/entry-header',
            null,
            array(
                'title' => (string) $card_args['title'],
                'title_tag' => $title_tag,
                'url' => (string) $card_args['url'],
                'meta' => (string) $card_args['meta'],
            )
        );
        ?>

        <?php if ((string) $card_args['summary'] !== '') : ?>
            <p class="entry-summary">
                <?php echo esc_html((string) $card_args['summary']); ?>
            </p>
        <?php endif; ?>

        <?php if ((string) $card_args['action_label'] !== '' && (string) $card_args['url'] !== '') : ?>
            <?php
            get_template_part(
                'components/link-row',
                null,
                array(
                    'text' => (string) $card_args['action_label'],
                    'href' => (string) $card_args['url'],
                )
            );
            ?>
        <?php endif; ?>
    </div>
</article>
