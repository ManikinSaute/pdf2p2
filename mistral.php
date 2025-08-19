<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}   


/**
 * Return an array of post IDs whose `mistral` flag is still false/0.
 *
 * @param string[] $post_types Optional. Which post-types to include. Default: pdf2p2_import & pdf2p2_gutenberg.
 * @return int[] Array of post IDs not yet processed by mistral OCR.
 */

// I need this function to get the unprocessed post IDs I think this can be turned into a big class to get all the post IDs in every function 

function pdf2p2_get_unprocessed_post_ids( array $post_types = [ 'pdf2p2_import', 'pdf2p2_gutenberg' ] ) : array {
    $args = [
        'post_type'      => $post_types,
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'   => 'mistral_processed',
                'value' => '0',
                'compare' => '=',
            ],
        ],
    ];
    return array_map( 'intval', get_posts( $args ) );
}

function pdf2p2_get_processed_post_ids(
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
        'meta_query'              => [[
            'key'     => 'mistral_processed',
            'value'   => 1,               
            'type'    => 'NUMERIC',
            'compare' => '='
        ]],
    ];

    return array_map( 'intval', get_posts( $args ) );
}

add_action('rest_api_init', function () {
    register_rest_route('pdf2p2/v1', '/check-files', [
        'methods'  => 'POST',
        'callback' => 'pdf2p2_check_files',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        }
    ]);
});


function pdf2p2_render_mistral_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }   
    echo '<div class="wrap">';
    echo '<h1>Check Posts</h1>';
    echo '<p>This page tell us what has or has not been processed by the OCR tool.</p>';
    echo '<h2>Processed Posts</h2>';

    $processed = pdf2p2_get_processed_post_ids();
    if ( ! empty( $processed ) ) {
        echo '<p>The latest 20 processed posts will are shown below.</p>';
        foreach ( $processed as $post_id ) {
            echo '<li>' . esc_html( get_the_title( $post_id ) ) . ' (ID: ' . esc_html( $post_id ) . ')</li>';
        }
        echo '</ul>';

        } else {
            echo '<p>There are no processed files to display.</p>';
        }
    echo '<h2>Unprocessed Posts</h2>';

    $unprocessed = pdf2p2_get_unprocessed_post_ids();
    if ( ! empty( $unprocessed ) ) {
        echo '<p>Unprocessed posts will are shown below.</p>';
        foreach ( $unprocessed as $post_id ) {
            echo '<li>' . esc_html( get_the_title( $post_id ) ) . ' (ID: ' . esc_html( $post_id ) . ')</li>';
        }
        echo '</ul>';

        } else {
            echo '<p>There are no unprocessed files to display.</p>';
        }

        echo '<h2>Check A Post</h2>';
        echo '<p>Enter one or more post IDs (comma-separated):</p>';
        echo '<form method="post" style="margin-top:20px;">';
        WP_nonce_field( 'pdf2p2_mistral', 'pdf2p2_mistral_nonce' );
        echo '  <label for="check_post_ids">Post IDs:</label> ';
        echo '  <input type="text" name="check_post_ids" id="check_post_ids" ';
        echo '         placeholder="e.g. 12,34,56" style="width:200px;" />';
        echo '  <button type="submit" class="button button-primary">Check Posts</button>';
        echo '</form>';
        echo '</div>';

        if ( isset($_POST['check_post_ids']) ) {
            if ( ! check_admin_referer( 'pdf2p2_mistral', 'pdf2p2_mistral_nonce' ) ) {
                echo '<div class="notice notice-error"><p>Invalid nonce. Please try again.</p></div>';
                return;
            }
            $raw   = sanitize_text_field( wp_unslash($_POST) ['check_post_ids'] );
            $parts = array_filter( array_map( 'intval', explode( ',', $raw ) ) );

            foreach ( $parts as $post_id ) {
                        pdf2p2_log( sprintf( 'mistral.php — button clicked with the following posts "%s"); .', $post_id ), 'INFO'  );
            }
        }

    if ( ! empty( $_POST['check_post_ids'] ) ) {
        $raw   = sanitize_text_field( wp_unslash( $_POST['check_post_ids'] ) );
        $ids   = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
        pdf2p2_log( sprintf( 'mistral.php — submit clicked. IDs: %s', implode( ', ', $ids ) ), 'INFO' );

        if ( ! empty( $ids ) ) {
            echo '<h2 style="margin-top:30px;">Preview of Selected Posts</h2>';
            echo '<ul>';

            foreach ( $ids as $post_id ) {
                $post = get_post( $post_id );
                if ( ! $post ) {

                    continue;
                }

                $title   = get_the_title( $post );
                $edit_url= get_edit_post_link( $post_id );
                $type_obj= get_post_type_object( $post->post_type );
                $type    = $type_obj ? $type_obj->labels->singular_name : $post->post_type;
                $date    = get_the_date( 'F j, Y', $post );
                $processed_raw = get_post_meta( $post_id, 'mistral_processed', true );
                $processed_label = 'Unknown';
                    if ( '0' === $processed_raw ) {
                $processed_label = 'No';
                    } elseif ( '1' === $processed_raw ) {
                    $processed_label = 'Yes';
                    }

                printf(
                    '<li><strong><a href="%1$s">%2$s</a></strong> (ID: %3$d)<br />
                     &mdash; Type: %4$s | Published: %5$s | Process Status: %6$s</li>',
                    esc_url( $edit_url ),
                    esc_html( $title ),
                    esc_html( $post_id ),
                    esc_html( $type ),
                    esc_html( $date ),
                    esc_html( $processed_label )
                );
            }

            echo '</ul>';
        } else {
            echo '<p><em>No valid post IDs found.</em></p>';
        }
    }
    echo '</div>';
}