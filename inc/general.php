<?php

/* `Add data-turbo="false" to all admin bar links so that Turbo does not mess with the admin
----------------------------------------------------------------------------------------------------*/

add_action('wp_before_admin_bar_render', function () {
    echo '<div data-turbo="false">';
});
add_action('wp_after_admin_bar_render', function () {
    echo '</div>';
});

/* `Stop the admin bar from pushing the page down and add proper offset */

add_action('get_header', function() {
    remove_action('wp_head', '_admin_bar_bump_cb');
});

add_action('wp_head', function () {
    if (is_admin_bar_showing()) {
        echo '<style>
            html { margin-top: 32px !important; }
            @media screen and (max-width: 782px) { html { margin-top: 46px !important; } }
            .fixed, .sticky { top: 32px !important; }
            @media screen and (max-width: 782px) { .fixed, .sticky { top: 46px !important; } }
        </style>';
    }
});



/* `Force admin bar to appear on the frontend
----------------------------------------------------------------------------------------------------*/

add_action('init', function () {
    if (is_user_logged_in()) {
        add_filter('show_admin_bar', '__return_true', 1000);
    }
});

/* `Make images fit their containers  (inside the_content and post thumbnail)
----------------------------------------------------------------------------------------------------*/

add_filter('wp_get_attachment_image_attributes', function ($attr) {
    if (strpos($attr['class'], 'h-auto max-w-full') === false) {
        $attr['class'] .= ' h-auto max-w-full';
    }
    return $attr;
});


/* `Add support for 'li_class' and 'li_class_X' to wp_nav_menu
----------------------------------------------------------------------------------------------------*/

add_filter('nav_menu_css_class', function ($classes, $item, $args, $depth) {
    if (isset($args->li_class)) {
        $classes[] = $args->li_class;
    }

    if (isset($args->{"li_class_$depth"})) {
        $classes[] = $args->{"li_class_$depth"};
    }

    return $classes;
}, 10, 4);


/* `Add support for 'submenu_class' and 'submenu_class_X' to wp_nav_menu
----------------------------------------------------------------------------------------------------*/

add_filter('nav_menu_submenu_css_class', function ($classes, $args, $depth) {
    if (isset($args->submenu_class)) {
        $classes[] = $args->submenu_class;
    }

    if (isset($args->{"submenu_class_$depth"})) {
        $classes[] = $args->{"submenu_class_$depth"};
    }

    return $classes;
}, 10, 3);