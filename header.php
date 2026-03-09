<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="starterspin">

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_template_directory_uri() ?>/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo get_template_directory_uri() ?>/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo get_template_directory_uri() ?>/favicon/favicon-16x16.png">
    <link rel="manifest" href="<?php echo get_template_directory_uri() ?>/favicon/site.webmanifest">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="content-type" content="<?php bloginfo('html_type') ?>; charset=<?php bloginfo('charset') ?>" />
    <?php wp_head(); ?>
</head>

<body <?php body_class('brand-site bg-background text-foreground antialiased'); ?>>
    <?php wp_body_open(); ?>
    <div id="page" class="min-h-screen flex flex-col">
        <div id="mobile-menu-container">
            <?php echo get_template_part('template-parts/mobile-menu'); ?>
        </div>
        <header class="brand-topbar">
            <div class="brand-shell">
                <div class="brand-topbar-frame">
                    <div class="brand-topbar-brand">
                        <?php get_template_part('components/brand-lockup', null, array('show_tagline' => false)); ?>
                    </div>

                    <div class="brand-topbar-actions">
                        <?php
                        wp_nav_menu(
                            array(
                                'container_id' => 'main-menu',
                                'container_class' => 'brand-desktop-nav',
                                'menu_class' => '',
                                'theme_location' => 'main-menu',
                                'fallback_cb' => false,
                                'walker' => new Walker_Nav_Menu_Tailwind(),
                            )
                        );
                        ?>

                        <div class="lg:hidden">
                            <?php
                            get_template_part(
                                'components/icon-button',
                                null,
                                array(
                                    'class' => 'mobile-menu-toggle',
                                    'icon' => 'menu',
                                    'aria_label' => 'Open navigation',
                                )
                            );
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div id="content" class="site-content">

            <main>
