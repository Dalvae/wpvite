<div class="mobile-menu-shell">
    <div class="mobile-menu-header">
        <?php get_template_part('components/brand-lockup', null, array('show_tagline' => false)); ?>

        <?php
        get_template_part(
            'components/icon-button',
            null,
            array(
                'class' => 'mobile-menu-toggle',
                'icon' => 'x',
                'aria_label' => 'Close navigation',
            )
        );
        ?>
    </div>

    <div class="mobile-menu-body">
        <?php
        wp_nav_menu(
            array(
                'container_id' => 'mobile-menu',
                'menu_class' => 'mobile-menu-nav',
                'theme_location' => 'mobile-menu',
                'li_class' => 'mobile-menu-item',
                'fallback_cb' => false,
            )
        );
        ?>
    </div>
</div>
