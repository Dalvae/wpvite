<?php
$args = wp_parse_args(
    $args,
    array(
        'items' => array(),
        'grid_class' => '',
        'aria_label' => '',
        'empty_title' => '',
        'empty_summary' => '',
    )
);

$items = array_values(array_filter((array) $args['items'], 'is_array'));
$collection_args = starter_get_page_collection_section_args('cards', $args);
?>
<?php if (!empty($items)) : ?>
    <<?php echo esc_attr((string) $collection_args['section_tag']); ?> <?php echo starter_render_html_attributes((array) $collection_args['section_attributes']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <?php foreach ($items as $item) : ?>
            <?php get_template_part('template-parts/page/summary-card', null, $item); ?>
        <?php endforeach; ?>
    </<?php echo esc_attr((string) $collection_args['section_tag']); ?>>
<?php else : ?>
    <?php get_template_part('template-parts/page/empty-state', null, (array) $collection_args['empty_state']); ?>
<?php endif; ?>
