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
/*  HIDE GRAVITY FORMS MENU FROM NON-ADMINS */
/*******************************************/
if ( ! current_user_can( 'administrator' ) ) {
    function theme_remove_gravityforms_menu(): void {
        remove_menu_page( 'gf_edit_forms' );
    }
    add_action( 'admin_menu', 'theme_remove_gravityforms_menu' );
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
/*  ADMIN — SCHERMATA MODIFICA TERMINE     */
/*  #edittag è di default max-width:800px, */
/*  scomodo con un builder a blocchi dentro */
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
/*  PAGE SLUG / TASSONOMIA → BODY CLASS    */
/*******************************************/
function add_page_slug_to_the_body( array $classes ): array {
    global $post;

    if ( isset( $post ) ) {
        $classes[] = $post->post_type . '_' . $post->post_name;
    }

    if ( is_tax() || is_category() || is_tag() ) {
        $term = get_queried_object();

        if ( $term instanceof WP_Term ) {
            $classes[] = 'tax_' . $term->taxonomy . '_' . $term->slug;
            $classes[] = $term->parent > 0
                ? 'tax_' . $term->taxonomy . '_child'
                : 'tax_' . $term->taxonomy . '_parent';
        }
    }

    return $classes;
}
add_filter( 'body_class', 'add_page_slug_to_the_body' );


/*******************************************/
/*  BLOCK OPTIONS (padding/margin/classi)  */
/*  Consuma il campo clone "options" del   */
/*  field group ACF "Block - Options"      */
/*  agganciato a ogni blocco del builder.  */
/*******************************************/
function render_option_padding( array $options ): string {
    $classes = '';

    if ( ( $options['padd_top'] ?? 0 ) > 0 ) {
        $classes .= ' padd-top-' . $options['padd_top'];
    }

    if ( ( $options['padd_bott'] ?? 0 ) > 0 ) {
        $classes .= ' padd-bott-' . $options['padd_bott'];
    }

    return $classes;
}

function render_option_margin( array $options ): string {
    $classes = '';

    if ( isset( $options['margin_top'] ) && 0 !== (int) $options['margin_top'] ) {
        $classes .= ' marg-top-' . $options['margin_top'];
    }

    if ( isset( $options['margin_bott'] ) && 0 !== (int) $options['margin_bott'] ) {
        $classes .= ' marg-bott-' . $options['margin_bott'];
    }

    return $classes;
}

function render_option_container( array $options ): string {
    if ( ! isset( $options['container_type'] ) ) {
        return '';
    }

    return $options['container_type'] ? 'container' : 'container-fluid';
}

// Classi CSS custom + padding/margin per un blocco, a partire dal campo
// clone "options" (field group "Block - Options"). Uso: `class="block<?= render_options($options) ?>"`.
function render_options( array $options ): string {
    $classes  = ! empty( $options['classes'] ) ? ' ' . $options['classes'] : '';
    $classes .= render_option_padding( $options );
    $classes .= render_option_margin( $options );

    return $classes;
}

function render_container( array $options ): string {
    return render_option_container( $options );
}

function render_bg_color( array $options ): string {
    return ! empty( $options['container_color'] )
        ? ' style="background-color:' . esc_attr( $options['container_color'] ) . ';"'
        : '';
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
