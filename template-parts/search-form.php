<form action="<?php bloginfo('url'); ?>/" method="get" class="join w-full">
    <label class="sr-only" for="search"><?php esc_html_e('Search', 'wpvite'); ?></label>
    <input
        type="text"
        name="s"
        id="search"
        placeholder="<?php echo esc_attr__('Search', 'wpvite'); ?>"
        value="<?php the_search_query(); ?>"
        class="input input-bordered join-item w-full"
    />
    <button type="submit" class="btn btn-primary join-item">
        <?php esc_html_e('Search', 'wpvite'); ?>
    </button>
</form>
