<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'template_include', 'pdf2p2_load_single_template', 99 );
function pdf2p2_load_single_template( $template ) {
    if ( is_singular( 'pdf2p2_import' ) || is_singular( 'pdf2p2_gutenberg' ) ) {
        $cpt = get_post_type();
        $file = plugin_dir_path( __FILE__ ) . "single-pdf2p2_gutenberg.php";
        if ( file_exists( $file ) ) {
            return $file;
        }
    }
    return $template;
}