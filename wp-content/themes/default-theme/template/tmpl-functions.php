<?php

/*******************************************/
/*  GOOGLE MAPS                            */
/*******************************************/
function my_acf_init(): void {
    acf_update_setting( 'google_api_key', get_field( 'google_maps_api', 'option' ) );
}
add_action( 'acf/init', 'my_acf_init' );

function maps_api_init(): void {
    $api = get_field( 'google_maps_api', 'option' ) ?: '';
    wp_enqueue_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api ) . '&libraries=places', array(), null, true );
}
add_action( 'wp_enqueue_scripts', 'maps_api_init' );


/*******************************************/
/*  VITE ASSET HELPER                      */
/*******************************************/
function theme_get_vite_manifest(): array {
    static $manifest = null;
    if ( null === $manifest ) {
        $manifest_path = get_template_directory() . '/assets/dist/.vite/manifest.json';
        if ( file_exists( $manifest_path ) ) {
            $manifest = json_decode( file_get_contents( $manifest_path ), true ) ?? array();
        } else {
            $manifest = array();
        }
    }
    return $manifest;
}

function theme_enqueue_assets(): void {
    $is_dev     = defined( 'VITE_DEV' ) && VITE_DEV;
    $theme_dir  = get_template();
    $theme_path = "wp-content/themes/{$theme_dir}";

    if ( $is_dev ) {
        $dev_url = 'http://localhost:5173';

        wp_enqueue_script( 'vite-client', "{$dev_url}/@vite/client", array(), null, false );
        wp_scripts()->add_data( 'vite-client', 'type', 'module' );

        wp_enqueue_script( 'theme-main', "{$dev_url}/{$theme_path}/assets/src/js/main.js", array(), null, true );
        wp_scripts()->add_data( 'theme-main', 'type', 'module' );
    } else {
        $manifest = theme_get_vite_manifest();
        $js_entry = "{$theme_path}/assets/src/js/main.js";

        if ( isset( $manifest[ $js_entry ] ) ) {
            wp_enqueue_script(
                'theme-main',
                get_template_directory_uri() . '/assets/dist/' . $manifest[ $js_entry ]['file'],
                array(),
                null,
                true
            );

            if ( ! empty( $manifest[ $js_entry ]['css'] ) ) {
                foreach ( $manifest[ $js_entry ]['css'] as $index => $css_file ) {
                    wp_enqueue_style(
                        "theme-style-{$index}",
                        get_template_directory_uri() . '/assets/dist/' . $css_file,
                        array(),
                        null
                    );
                }
            }
        }
    }
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_assets' );


/*******************************************/
/*  RENDER HELPERS                         */
/*******************************************/
function render_theme_component( string $component_name, array $args = array() ): void {
    include get_template_directory() . '/template/components/' . $component_name . '.php';
}

function render_theme_block( string $block_name, array $args = array() ): void {
    if ( isset( $args['id_block'] ) ) {
        $fields = is_string( $args['id_block'] )
            ? get_field( $args['id_block'] )
            : $args['id_block'];
    }
    include get_template_directory() . '/template/blocks/' . $block_name . '.php';
}


/*******************************************/
/*  UTILITIES                              */
/*******************************************/
function get_image_size( array $field, string $size ): string {
    return $field['sizes'][ $size ] ?? '';
}

function get_svg( string $name, string $path_folder = '' ): string {
    if ( '' === $path_folder ) {
        $path_folder = get_template_directory() . '/images/svg/';
    }
    $file = $path_folder . $name . '.svg';
    return file_exists( $file ) ? file_get_contents( $file ) : '';
}

function print_pretty_array( mixed $variable ): void {
    echo '<pre>' . esc_html( print_r( $variable, true ) ) . '</pre>';
}
