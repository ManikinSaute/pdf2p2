<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pdf2p2_send_post_to_mistral_ocr( $post_id ) {
    $api_key = get_option( 'pdf2p2_api_key', '' );
    if ( ! $api_key ) {
        pdf2p2_log( 'mistral-send.php - API key not configured.', 'ERROR' );
        return new WP_Error( 'no_api_key', 'Mistral OCR API key not configured.' );
    }
    $post = get_post( $post_id );
    if ( ! $post ) {
        pdf2p2_log( sprintf( 'mistral-send.php - Invalid post ID: %d', $post_id ), 'ERROR' );
        return new WP_Error( 'invalid_post', 'Invalid post ID.' );
    }
    $file_url = get_post_meta( $post_id, 'pdf2p2_original_file_path', true );
    if ( ! $file_url ) {
        pdf2p2_log( sprintf( 'mistral-send.php - No original PDF URL found for post ID: %d', $post_id ), 'ERROR' );
        return new WP_Error( 'no_url', 'No original PDF URL found in post meta.' );
    }
    $payload = [
        'model'                => 'mistral-ocr-latest',
        'document'             => [
            'type'         => 'document_url',
            'document_url' => esc_url_raw( $file_url ),
        ],
        'include_image_base64' => true,
    ];
    $response = wp_remote_post(
        'https://api.mistral.ai/v1/ocr',
        [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . sanitize_text_field( $api_key ),
            ],
            'body'    => wp_json_encode( $payload ),
            'timeout' => 120,
        ]
    );
    if ( is_wp_error( $response ) ) {
        pdf2p2_log( sprintf( 'mistral-send.php - Request error: %s', $response->get_error_message() ), 'ERROR' );
        return $response;
    }
    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = wp_remote_retrieve_body( $response );
    $data        = json_decode( $body, true );
    if ( 200 !== $status_code || ! is_array( $data ) || empty( $data['pages'] ) ) {
        pdf2p2_log(
            sprintf( 'mistral-send.php - Invalid response. HTTP %d: %s', $status_code, substr( $body, 0, 500 ) ),
            'ERROR'
        );
        return new WP_Error( 'invalid_response', 'Invalid or empty OCR response.' );
    }
    $new_content = '';
    foreach ( $data['pages'] as $page ) {
        $new_content .= $page['text'] . "\n\n";
    }
    return $new_content;
}


/* 
function pdf2p2_send_post_to_mistral_ocr( $post_id ) {
    $api_key = get_option( 'pdf2p2_api_key', '' );
    if ( ! $api_key ) {
        pdf2p2_log( 'mistral-send.php - API key not configured.', 'ERROR' );
        return new WP_Error( 'no_api_key', 'mistral OCR API key not configured.' );
    }
    $post = get_post( $post_id );
    if ( ! $post ) {
        pdf2p2_log( sprintf( 'mistral-send.php - Invalid post ID: %d', $post_id ), 'ERROR' );
        return new WP_Error( 'invalid_post', 'Invalid post ID.' );
    }
    $file_url = get_post_meta( $post_id, 'pdf2p2_original_file_path', true );
    if ( ! $file_url ) {
        pdf2p2_log( sprintf( 'mistral-send.php - No original PDF URL found for post ID: %d', $post_id ), 'ERROR' );
        return new WP_Error( 'no_url', 'No original PDF URL found in post meta.' );
    }
    $payload = [
        'model'                => 'mistral-ocr-latest',
        'document'             => [
            'type'         => 'document_url',
            'document_url' => $file_url,
        ],
        'include_image_base64' => true,
    ];
    $ch = curl_init( 'https://api.mistral.ai/v1/ocr' );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => wp_json_encode( $payload ),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
        CURLOPT_TIMEOUT        => 120,
    ] );
    $response = curl_exec( $ch );
    $err      = curl_error( $ch );
    curl_close( $ch );
    if ( $err ) {
        pdf2p2_log( sprintf( 'mistral-send.php - cURL error: %s', $err ), 'ERROR' );
        return new WP_Error( 'curl_error', $err );
    }
    $data = json_decode( $response, true );
    if ( ! is_array( $data ) || empty( $data['pages'] ) ) {
        pdf2p2_log( 'mistral-send.php - Invalid or empty response.', 'ERROR' );
        return new WP_Error( 'invalid_response', 'Invalid or empty OCR response.' );
    }
    $new_content = '';
    foreach ( $data['pages'] as $page ) {
        $idx = isset( $page['index'] ) ? ( (int) $page['index'] + 1 ) : null;
        if ( $idx ) {
            $new_content .= "\n\n### Page {$idx}\n\n";
        }
        if ( ! empty( $page['markdown'] ) ) {
            pdf2p2_log( sprintf( 'mistral-send.php - Page %s: markdown length %d', (string) $idx, strlen( $page['markdown'] ) ), 'INFO' );
            $new_content .= $page['markdown'] . "\n\n";
        }
        if ( ! empty( $page['images'] ) && is_array( $page['images'] ) ) {
            pdf2p2_log( sprintf( 'mistral-send.php - Page %s: %d images found', (string) $idx, count( $page['images'] ) ), 'INFO' );
            foreach ( $page['images'] as $img ) {
                $raw  = is_array( $img )
                    ? ( $img['data'] ?? $img['image_base64'] ?? '' )
                    : ( is_string( $img ) ? $img : '' );

                $mime = is_array( $img )
                    ? ( $img['mime_type'] ?? 'image/jpeg' )
                    : 'image/jpeg';

                if ( ! $raw ) {
                    pdf2p2_log( sprintf( 'mistral-send.php - Page %s: skipped empty image', (string) $idx ), 'WARNING' );
                    continue;
                }
                if ( strncmp( $raw, 'data:', 5 ) !== 0 ) {
                    $raw = 'data:' . $mime . ';base64,' . preg_replace( '#\s+#', '', $raw );
                    pdf2p2_log( sprintf( 'mistral-send.php - Page %s: image converted to base64 %s', (string) $idx, $mime ), 'INFO' );
                }
                $new_content .= '![](' . $raw . ")\n\n";
            }
        } else {
            pdf2p2_log( sprintf( 'mistral-send.php - Page %s: no images found', (string) $idx ), 'INFO' );
        }
    }
    if ( $new_content !== '' ) {
        list( $new_content, $img_count ) = pdf2p2_sideload_images_in_markdown( $new_content, (int) $post_id );
        pdf2p2_log( sprintf( 'mistral-send.php - Sideloaded %d images for post %d', (int) $img_count, (int) $post_id ), 'INFO' );
    }
    if ( $new_content && $new_content !== $post->post_content ) {
        wp_update_post( [
            'ID'           => $post_id,
            'post_content' => wp_slash( $new_content ),
        ] );
    }

    update_post_meta( $post_id, 'mistral_processed', '1' );
    pdf2p2_log( sprintf( 'mistral-send.php — File processed successfully for post %d (source: %s).', (int) $post_id, esc_url_raw( $file_url ) ), 'SUCCESS' );

    return true;
}

*/


function pdf2p2_sideload_images_in_markdown( string $md, int $post_id ): array {
    $count = 0;

    if ( ! preg_match_all('/!\[[^\]]*]\(([^)]+)\)/', $md, $all, PREG_SET_ORDER) ) {
        return [ $md, 0 ];
    }

    foreach ( $all as $m ) {
        $full  = $m[0];
        $src   = trim( $m[1], " \t\n\r\0\x0B\"'" );

        if ( preg_match('#^image/(png|jpe?g|gif|webp);base64,#i', $src) ) {
            $src = 'data:' . $src;
        }

        if ( preg_match('#^data:image/[a-z0-9.+-]+;base64,#i', $src) ) {
            $url = sideload_single_data_image_logged( $src, $post_id );
            if ( $url ) {
                $count++;
                $new = str_replace( $m[1], $url, $full );
                $md  = str_replace( $full, $new, $md );
            }
        }
    }

    return [ $md, $count ];
}


function pdf2p2_render_mistral_send_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

    echo '<div class="wrap">';
    echo '<h1>Send Data To OCR</h1>';
    echo '<p>This page allows you to send selected posts to the Mistral OCR service for processing.</p>';
    echo '<h2>Processed Posts</h2>';
    echo '<p>The latest 20 processed posts will show below.</p>';

    $processed = pdf2p2_get_processed_post_ids();
    if ( ! empty( $processed ) ) {
        foreach ( $processed as $post_id ) {
            printf(
                '<p>Processed posts: <a href="%1$s">%2$s</a> (ID %3$d)</p>',
                esc_url( get_edit_post_link( $post_id ) ),
                esc_html( get_the_title( $post_id ) ),
                intval( $post_id )
            );
        }
    } else {
        echo '<p>No unprocessed documents</p>';
    }

    echo '<h2>Unprocessed Posts</h2>';
    echo '<p>Unprocessed posts will are shown below.</p>';

    $unprocessed = pdf2p2_get_unprocessed_post_ids();
    if ( ! empty( $unprocessed ) ) {
        foreach ( $unprocessed as $post_id ) {
            printf(
                '<p>Unprocessed: <a href="%1$s">%2$s</a> (ID %3$d)</p>',
                esc_url( get_edit_post_link( $post_id ) ),
                esc_html( get_the_title( $post_id ) ),
                intval( $post_id )
            );
        }
    } else {
        echo '<p>No documents to process</p>';
    }
// not sure about sanatisation here
    $prefill = '';
    if ( isset( $_POST['send_mistral_post_ids'] ) ) {
        $prefill_raw = wp_unslash( $_POST['send_mistral_post_ids'] ); 
        $prefill     = esc_attr( sanitize_text_field( $prefill_raw ) );
    }

    echo '<h2>Send Posts</h2>';
    echo '<p>Add a post ID or many in CSV format.</p>';
    echo '<form method="post">';
    wp_nonce_field( 'pdf2p2_send_ocr', 'pdf2p2_send_ocr_nonce' );
    echo '<input type="text" name="send_mistral_post_ids" style="width:300px;" '
    . 'placeholder="e.g. 12,34,56" '
    . 'value="' . esc_attr( $prefill ) . '">';
        submit_button( 'Send to OCR', 'primary', 'send_ocr' );
        echo '</form>';

    if ( ! empty( $_POST['send_ocr'] )
      && check_admin_referer( 'pdf2p2_send_ocr', 'pdf2p2_send_ocr_nonce' )
    ) {
        $raw = sanitize_text_field( wp_unslash( $_POST['send_mistral_post_ids'] ) );
        $ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
        pdf2p2_log( sprintf( 'mistral-send.php — submit clicked. IDs: %s', implode( ', ', $ids ) ), 'INFO' );
        if ( $ids ) {
            echo '<h2>OCR Results</h2>';
            foreach ( $ids as $post_id ) {
                $result = pdf2p2_send_post_to_mistral_ocr( $post_id );
                if ( is_wp_error( $result ) ) {
                    echo '<p style="color:red;"><strong>' 
                       . esc_html( $result->get_error_message() ) 
                       . '</strong></p>';
                } else {
                    pdf2p2_log( sprintf( 'mistral-send.php — post %d processed successfully.', $post_id ), 'INFO' );
                    echo '<p>' . 
                    sprintf('%1$s (ID %2$d) processed successfully.',                        
                    esc_html( get_the_title( $post_id ) ),
                    intval( $post_id )
                    ) . 
                    '</p>';
                }
            }
        } else {
            echo '<p><em>' . esc_html__( 'No valid post IDs provided.', 'pdf2p2' ) . '</em></p>';
        }
    }
    echo '</div>';
}