<?php get_header(); ?>

<div class="<?php echo esc_attr(starter_get_page_shell_classes()); ?>">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,var(--ds-layout-sidebar-max))]">
                <div>
                    <?php get_template_part('template-parts/content', 'single'); ?>
                </div>
                <div>
                    <?php get_sidebar(); ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
