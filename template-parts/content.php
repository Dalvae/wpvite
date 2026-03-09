<article id="post-<?php the_ID(); ?>" <?php post_class(starter_get_page_surface_classes('content')); ?>>
    <?php
    get_template_part(
        'components/entry-header',
        null,
        array(
            'title' => get_the_title(),
            'title_tag' => 'h2',
            'url' => get_permalink(),
            'meta' => get_the_date(),
        )
    );
    ?>

    <?php if ( is_search() || is_archive() ) : ?>

        <div class="entry-summary page-prose">
            <?php the_excerpt(); ?>
        </div>

    <?php else : ?>

        <div class="entry-content page-prose">
            <?php
            /* translators: %s: Name of current post */
            the_content(
                sprintf(
                    'Continue reading %s',
                    the_title( '<span class="screen-reader-text">"', '"</span>', false )
                )
            );

            wp_link_pages(
                array(
                    'before'      => '<div class="page-links"><span class="page-links-title">Pages:</span>',
                    'after'       => '</div>',
                    'link_before' => '<span>',
                    'link_after'  => '</span>',
                    'pagelink'    => '<span class="screen-reader-text">Page </span>%',
                    'separator'   => '<span class="screen-reader-text">, </span>',
                )
            );
            ?>
        </div>

    <?php endif; ?>

</article>
