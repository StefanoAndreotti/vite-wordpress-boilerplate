<?php

/*******************************************/
/*  GOOGLE MAPS                            */
/*******************************************/
function my_acf_init(): void {
    acf_update_setting( 'google_api_key', get_field( 'google_maps_api', 'option' ) );
}
add_action( 'acf/init', 'my_acf_init' );

function maps_api_init(): void {
    $api = get_field( 'google_maps_api', 'option' );
    if ( ! $api ) {
        return;
    }
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
        // Se il dev server Vite è servito in HTTPS sul dominio locale (vedi
        // vite.config.js: certificato Valet per LOCAL_DOMAIN), allinea qui
        // l'host/protocollo usato per caricare gli script — altrimenti il
        // browser blocca il mixed content (pagina https, script http).
        $protocol = is_ssl() ? 'https' : 'http';
        $host     = is_ssl() ? ( defined( 'LOCAL_DOMAIN' ) ? LOCAL_DOMAIN : $_SERVER['HTTP_HOST'] ) : 'localhost';
        $dev_url  = "{$protocol}://{$host}:5173";

        wp_enqueue_script( 'vite-client', "{$dev_url}/@vite/client", array(), null, false );
        wp_enqueue_script( 'theme-main', "{$dev_url}/{$theme_path}/assets/src/js/main.js", array(), null, true );
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

// Add type="module" to Vite script tags
add_filter( 'script_loader_tag', function( string $tag, string $handle ): string {
    if ( in_array( $handle, array( 'vite-client', 'theme-main' ), true ) ) {
        $tag = str_replace( '<script ', '<script type="module" ', $tag );
    }
    return $tag;
}, 10, 2 );


/*******************************************/
/*  RENDER HELPERS                         */
/*******************************************/
function render_theme_component( string $component_name, array $args = array() ): void {
    // Chiusura statica: isola lo scope (solo $args è visibile dentro il
    // template incluso, niente leak di variabili locali della funzione
    // chiamante) e basename() impedisce path traversal se $component_name
    // arrivasse mai da input non fidato.
    ( static function ( string $name, array $args ): void {
        include get_template_directory() . '/template/components/' . basename( $name ) . '.php';
    } )( $component_name, $args );
}

function render_theme_block( string $block_name, array $args = array() ): void {
    ( static function ( string $name, array $args ): void {
        $fields = isset( $args['id_block'] ) && is_array( $args['id_block'] )
            ? $args['id_block']
            : ( isset( $args['id_block'] ) ? get_field( $args['id_block'] ) : array() );
        $options = $args['options'] ?? array();
        include get_template_directory() . '/template/blocks/' . basename( $name ) . '.php';
    } )( $block_name, $args );
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

// Un campo Link ACF vuoto (o l'intera riga di un repeater non compilata)
// torna `false` invece di un array: normalizza per evitare warning/fatal
// "array offset on bool/null" quando si accede a $link['url'] ecc. Il
// fallback a $field stesso copre il caso in cui il valore passato sia già
// l'array del link (non annidato sotto una sub-key), e array_merge riempie
// le chiavi mancanti se il link è compilato solo parzialmente.
function get_acf_link( mixed $field, string $key = 'link' ): array {
    $empty = array( 'url' => '', 'title' => '', 'target' => '' );

    if ( ! is_array( $field ) ) {
        return $empty;
    }

    $link = $field[ $key ] ?? $field;
    return is_array( $link ) ? array_merge( $empty, $link ) : $empty;
}

// Costruisce la traccia breadcrumb (Home > ... > pagina corrente) per il
// contesto corrente: termine di tassonomia (con i suoi antenati), pagina
// (con le sue pagine antenate), singolo di un CPT (con l'archivio e/o la
// prima tassonomia gerarchica assegnata), archivio, ricerca, 404. Ogni voce
// è ['title' => ..., 'url' => '']; l'ultima voce ha url vuoto (pagina corrente).
function theme_get_breadcrumb_items(): array {
    $items = array(
        array(
            'title' => __( 'Home', 'default-theme' ),
            'url'   => home_url( '/' ),
        ),
    );

    if ( is_front_page() ) {
        return array( $items[0] );
    }

    if ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            $ancestor_ids = array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) );
            foreach ( $ancestor_ids as $ancestor_id ) {
                $ancestor = get_term( $ancestor_id, $term->taxonomy );
                if ( $ancestor instanceof WP_Term ) {
                    $link    = get_term_link( $ancestor );
                    $items[] = array(
                        'title' => $ancestor->name,
                        'url'   => is_wp_error( $link ) ? '' : $link,
                    );
                }
            }
            $items[] = array(
                'title' => $term->name,
                'url'   => '',
            );
        }
    } elseif ( is_singular() ) {
        $post = get_queried_object();
        if ( $post instanceof WP_Post ) {
            if ( 'page' === $post->post_type ) {
                $ancestor_ids = array_reverse( get_post_ancestors( $post ) );
                foreach ( $ancestor_ids as $ancestor_id ) {
                    $items[] = array(
                        'title' => get_the_title( $ancestor_id ),
                        'url'   => get_permalink( $ancestor_id ),
                    );
                }
            } else {
                $post_type_obj = get_post_type_object( $post->post_type );
                if ( $post_type_obj && $post_type_obj->has_archive ) {
                    $archive_link = get_post_type_archive_link( $post->post_type );
                    if ( $archive_link ) {
                        $items[] = array(
                            'title' => $post_type_obj->labels->name,
                            'url'   => $archive_link,
                        );
                    }
                }

                foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy ) {
                    if ( ! $taxonomy->hierarchical ) {
                        continue;
                    }
                    $terms = get_the_terms( $post, $taxonomy->name );
                    if ( $terms && ! is_wp_error( $terms ) ) {
                        $link = get_term_link( $terms[0] );
                        if ( ! is_wp_error( $link ) ) {
                            $items[] = array(
                                'title' => $terms[0]->name,
                                'url'   => $link,
                            );
                        }
                        break;
                    }
                }
            }

            $items[] = array(
                'title' => get_the_title( $post ),
                'url'   => '',
            );
        }
    } elseif ( is_post_type_archive() ) {
        $items[] = array(
            'title' => post_type_archive_title( '', false ),
            'url'   => '',
        );
    } elseif ( is_search() ) {
        $items[] = array(
            /* translators: %s: search query */
            'title' => sprintf( __( 'Risultati per: %s', 'default-theme' ), get_search_query() ),
            'url'   => '',
        );
    } elseif ( is_404() ) {
        $items[] = array(
            'title' => __( 'Pagina non trovata', 'default-theme' ),
            'url'   => '',
        );
    }

    return $items;
}
