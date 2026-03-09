<?php get_header(); ?>

<div class="<?php echo esc_attr(starter_get_page_shell_classes()); ?>">
    <?php if (have_posts()) : ?>
        <div class="page-collection page-collection--stack">
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('template-parts/content', get_post_format()); ?>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
