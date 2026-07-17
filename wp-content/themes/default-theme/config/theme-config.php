<?php

/*******************************************/
/*  CUSTOM MENU                            */
/*******************************************/
register_nav_menus( array(
    'primary' => __( 'Menu header', 'config' ),
    'footer'  => __( 'Menu footer', 'config' ),
    'lingue'  => __( 'Menu lingue', 'config' ),
) );


/*******************************************/
/*  REMOVE TAG                             */
/*******************************************/
function myprefix_unregister_tags(): void {
    unregister_taxonomy_for_object_type( 'post_tag', 'post' );
}
add_action( 'init', 'myprefix_unregister_tags' );


/*******************************************/
/*  ACF OPTIONS PAGES                      */
/*******************************************/
// Hook su 'acf/init' (non top-level): è il momento in cui ACF garantisce che
// le sue funzioni siano disponibili, indipendentemente dall'ordine di
// caricamento di plugin/tema.
function theme_register_acf_options_pages(): void {
    if ( ! function_exists( 'acf_add_options_page' ) ) {
        return;
    }

    acf_add_options_page( array(
        'page_title' => 'Campi globali',
        'menu_title' => 'Campi globali',
        'menu_slug'  => 'theme-general-settings',
        'capability' => 'edit_posts',
        'redirect'   => true,
    ) );

    acf_add_options_sub_page( array(
        'page_title'  => 'Google Maps - API',
        'menu_title'  => 'Google Maps - API',
        'parent_slug' => 'theme-general-settings',
    ) );
}
add_action( 'acf/init', 'theme_register_acf_options_pages' );


/*******************************************/
/*  ADMIN — SCHERMATA MODIFICA TERMINE     */
/*  #edittag è max-width:800px di default, */
/*  scomodo se dentro ci sono campi ACF a  */
/*  builder di blocchi                     */
/*******************************************/
function theme_admin_edit_tag_full_width(): void {
    $screen = get_current_screen();
    if ( ! $screen || 'term' !== $screen->base ) {
        return;
    }
    ?>
    <style>
        #edittag { max-width: 100%; }
    </style>
    <?php
}
add_action( 'admin_head', 'theme_admin_edit_tag_full_width' );


/*******************************************/
/*  EXCERPT                                */
/*******************************************/
function theme_custom_excerpt_length( int $length ): int {
    return 20;
}
add_filter( 'excerpt_length', 'theme_custom_excerpt_length', 999 );

function theme_excerpt_more( string $more ): string {
    return '...';
}
add_filter( 'excerpt_more', 'theme_excerpt_more' );


/*******************************************/
/*  ADMIN BAR CLEANUP                      */
/*******************************************/
function theme_admin_bar_remove(): void {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu( 'wp-logo' );
    $wp_admin_bar->remove_menu( 'comments' );
}
add_action( 'wp_before_admin_bar_render', 'theme_admin_bar_remove', 0 );


/*******************************************/
/*  HIDE CF7 MENU FROM NON-ADMINS          */
/*******************************************/
if ( ! current_user_can( 'administrator' ) ) {
    function theme_remove_cf7_menu(): void {
        remove_menu_page( 'wpcf7' );
    }
    add_action( 'admin_menu', 'theme_remove_cf7_menu' );
}


/*******************************************/
/*  POST NAVIGATION CLASSES                */
/*******************************************/
add_filter( 'next_posts_link_attributes', 'theme_next_posts_link_attributes' );
add_filter( 'previous_posts_link_attributes', 'theme_prev_posts_link_attributes' );

function theme_next_posts_link_attributes(): string {
    return 'class="prev-post"';
}
function theme_prev_posts_link_attributes(): string {
    return 'class="next-post"';
}


/*******************************************/
/*  CONTENT SECURITY POLICY                */
/*******************************************/
function theme_send_csp_headers(): void {
    // Disable CSP in development (Vite dev server uses different origins)
    if ( defined( 'VITE_DEV' ) && VITE_DEV ) {
        return;
    }

    $directives = apply_filters( 'theme_csp_directives', array(
        'default-src' => "'self'",
        'script-src'  => "'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://maps.googleapis.com",
        'style-src'   => "'self' 'unsafe-inline' https://fonts.googleapis.com",
        'font-src'    => "'self' https://fonts.gstatic.com",
        'img-src'     => "'self' data: https:",
        'connect-src' => "'self' https://www.google-analytics.com https://maps.googleapis.com",
        'frame-src'   => "'self' https://www.google.com",
        'worker-src'  => "'self' blob:",
        'object-src'  => "'none'",
        'base-uri'    => "'self'",
    ) );

    $policy = '';
    foreach ( $directives as $directive => $value ) {
        $policy .= $directive . ' ' . $value . '; ';
    }

    header( 'Content-Security-Policy: ' . trim( $policy ) );
}
add_action( 'send_headers', 'theme_send_csp_headers' );
