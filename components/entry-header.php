<?php
$args = wp_parse_args(
    $args,
    array(
        'title' => '',
        'title_tag' => 'h2',
        'url' => '',
        'meta' => '',
        'class' => '',
        'title_class' => '',
        'meta_class' => '',
        'link_class' => '',
    )
);

$title = trim((string) $args['title']);
if ($title === '') {
    return;
}

$title_tag = strtolower(trim((string) $args['title_tag']));
if (!in_array($title_tag, array('h1', 'h2', 'h3'), true)) {
    $title_tag = 'h2';
}

$url = trim((string) $args['url']);
$meta = trim((string) $args['meta']);
?>
<header class="<?php echo esc_attr(starter_merge_classes('entry-header', (string) $args['class'])); ?>">
    <<?php echo esc_attr($title_tag); ?> class="<?php echo esc_attr(starter_merge_classes('entry-title', (string) $args['title_class'])); ?>">
        <?php if ($url !== '') : ?>
            <a href="<?php echo esc_url($url); ?>" class="<?php echo esc_attr(starter_merge_classes('entry-link', (string) $args['link_class'])); ?>">
                <?php echo esc_html($title); ?>
            </a>
        <?php else : ?>
            <?php echo esc_html($title); ?>
        <?php endif; ?>
    </<?php echo esc_attr($title_tag); ?>>

    <?php if ($meta !== '') : ?>
        <p class="<?php echo esc_attr(starter_merge_classes('entry-meta', (string) $args['meta_class'])); ?>">
            <?php echo esc_html($meta); ?>
        </p>
    <?php endif; ?>
</header>
