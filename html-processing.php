<?php

function pdf2p2_get_html_processed_ids(
    array $post_types = [ 'pdf2p2_import', 'pdf2p2_gutenberg' ],
    int $limit = 20,
    string $orderby = 'date',
    string $order = 'DESC'
) : array {
    $orderby = ( $orderby === 'modified' ) ? 'modified' : 'date';
    $order   = ( strtoupper( $order ) === 'ASC' ) ? 'ASC' : 'DESC';

    $args = [
        'post_type'               => $post_types,
        'post_status'             => 'any',
        'posts_per_page'          => $limit,
        'fields'                  => 'ids',
        'orderby'                 => $orderby,
        'order'                   => $order,
        'no_found_rows'           => true,
        'update_post_meta_cache'  => false,
        'update_post_term_cache'  => false,
        'ignore_sticky_posts'     => true,
        'suppress_filters'        => true,
        'meta_query'              => [[
            'key'     => 'html_processed',
            'value'   => 1,               
            'type'    => 'NUMERIC',
            'compare' => '='
        ]],
    ];
    return array_map( 'intval', get_posts( $args ) );
}


function pdf2p2_get_html_unprocessed_ids(
    array $post_types = [ 'pdf2p2_import', 'pdf2p2_gutenberg' ],
    int $limit = 20,
    string $orderby = 'date',
    string $order = 'DESC'
) : array {
    $orderby = ( $orderby === 'modified' ) ? 'modified' : 'date';
    $order   = ( strtoupper( $order ) === 'ASC' ) ? 'ASC' : 'DESC';

    $args = [
        'post_type'               => $post_types,
        'post_status'             => 'any',
        'posts_per_page'          => $limit,
        'fields'                  => 'ids',
        'orderby'                 => $orderby,
        'order'                   => $order,
        'no_found_rows'           => true,
        'update_post_meta_cache'  => false,
        'update_post_term_cache'  => false,
        'ignore_sticky_posts'     => true,
        'suppress_filters'        => true,
        'meta_query'              => [
            'relation' => 'OR',
            [
                'key'     => 'html_processed',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => 'html_processed',
                'value'   => 1,
                'type'    => 'NUMERIC',
                'compare' => '!=',
            ],
        ],
    ];

    return array_map( 'intval', get_posts( $args ) );
}


/**
 * Process the Markdown content of a post and convert it to HTML.
 *
 * @param int $post_id The ID of the post to process.
 * @return bool True on success, false on failure.
 */
function pdf2p2_process_to_html( $post_id ): bool {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'pdf2p2_import' ) {
        return false;
    }

    if ( ! class_exists( 'Parsedown' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'Parsedown.php';
    }

    $Parsedown    = new Parsedown();
    $markdown     = (string) $post->post_content;
    $html_content = $Parsedown->text( $markdown );

    if ( function_exists( 'sideload_embedded_images' ) ) {
        $processed = sideload_embedded_images( $html_content, (int) $post_id );
        if ( is_string( $processed ) && $processed !== '' ) {
            $html_content = $processed;
        }
    }

    $updated = wp_update_post( [
        'ID'           => $post_id,
        'post_content' => $html_content,
    ], true );

    if ( is_wp_error( $updated ) ) {
        return false;
    }
    pdf2p2_log( sprintf( 'html-processing.php — all good. ID: %s' , $post_id) , 'SUCCESS' );
    update_post_meta( $post_id, 'html_processed', '1' );
    return true;
}


function pdf2p2_render_html_page() {
    echo '<div class="wrap">';
    echo '<h1>Convert Markdown content to HTML</h1>';
    echo '<p>This page lists posts that have been processed by the OCR tool and are ready for Markdown to HTML conversion.</p>';
    echo '<h2>Posts Not Converted To HTML</h2>';
    $to_convert = pdf2p2_get_html_unprocessed_ids();

    if ( empty( $to_convert ) ) {
        echo '<p>No posts with Markdown content ready for HTML conversion.</p>';
    } else {
        echo '<p>Posts with Markdown content that can be converted to HTML:</p>';
        echo '<ul>';
        foreach ( $to_convert as $post_id ) {
            $title = get_the_title( $post_id );
            echo '<li>' . esc_html( $title ) . ' (ID: ' . intval( $post_id ) . ')</li>';
        }
        echo '</ul>';
    }

    echo '<h2>Posts converted to HTML</h2>';
    echo '<p>Posts that have been converted to HTML will are shown below: (last 20)</p>';

    $converted = pdf2p2_get_html_processed_ids();

    if ( empty( $converted ) ) {
        echo '<p>No posts with HTML have been converted from Markdown</p>';
    } else {
        echo '<p>Posts with HTML that have been converted from Markdown:</p>';
        echo '<ul>';
        foreach ( $converted as $post_id ) {
            $title = get_the_title( $post_id );
            echo '<li>' . esc_html( $title ) . ' (ID: ' . intval( $post_id ) . ')</li>';
        }
        echo '</ul>';
    }
    echo '<h2>Convert MD to HTML</h2>';
    echo '<p>Add a post ID or many in CSV format.</p>';
    echo '<form method="post">';
    wp_nonce_field( 'pdf2p2_convert', 'pdf2p2_convert_nonce' );
    echo '<input type="text" name="send_convert_post_ids" style="width:300px;" '
       . 'placeholder="e.g. 12,34,56" '
       . 'value="' . ( isset( $_POST['send_convert_post_ids'] ) 
            ? esc_attr( $_POST['send_convert_post_ids'] ) 
            : '' ) . '">';
    submit_button( __( 'Send to HTML', 'pdf2p2' ), 'primary', 'send_convert' );
    echo '</form>';

    if ( ! empty( $_POST['send_convert'] )
      && check_admin_referer( 'pdf2p2_convert', 'pdf2p2_convert_nonce' )
    ) {
        $raw = sanitize_text_field( wp_unslash( $_POST['send_convert_post_ids'] ) );
        $ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
        pdf2p2_log( sprintf( 'html-processing.php — submit clicked. IDs: %s', implode( ', ', $ids ) ), 'INFO' );
        if ( $ids ) {
            echo '<h2>' . esc_html__( 'Convert Results', 'pdf2p2' ) . '</h2>';
            foreach ( $ids as $post_id ) {
                $result = pdf2p2_process_to_html( $post_id );
                if ( ! $result ) {
                echo '<p style="color:red;"><strong>' .
                    sprintf( esc_html__( '%1$s (ID %2$d) failed to process.', 'pdf2p2' ),
                    esc_html( get_the_title( $post_id ) ), intval( $post_id ) ) .
                    '</strong></p>';
                } else {
                echo '<p>' . sprintf(
                    esc_html__( '%1$s (ID %2$d) processed successfully.', 'pdf2p2' ),
                    esc_html( get_the_title( $post_id ) ),
                    intval( $post_id )
                ) . '</p>';
                }
            }
        } else {
            echo '<p><em>' . esc_html__( 'No valid post IDs provided.', 'pdf2p2' ) . '</em></p>';
        }
    }
    echo '</div>';
}