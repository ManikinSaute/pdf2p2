<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
        $idx = isset( $page['index'] ) ? intval( $page['index'] ) + 1 : '';
        pdf2p2_log( sprintf( 'mistral-send.php - Processing page index: %s', $idx ), 'INFO' );
        if ( $idx !== '' ) {
            $new_content .= "\n\n### Page {$idx}\n\n";
        }
        if ( ! empty( $page['markdown'] ) ) {
            pdf2p2_log( sprintf( 'mistral-send.php - Page %s: markdown length %d', $idx, strlen( $page['markdown'] ) ), 'INFO' );
            $new_content .= $page['markdown'] . "\n\n";
        }
        if ( ! empty( $page['images'] ) && is_array( $page['images'] ) ) {
            pdf2p2_log( sprintf( 'mistral-send.php - Page %s: %d images found', $idx, count( $page['images'] ) ), 'INFO' );
            foreach ( $page['images'] as $img ) {
                $raw  = is_array( $img )
                    ? ( $img['data'] ?? $img['image_base64'] ?? '' )
                    : ( is_string( $img ) ? $img : '' );
                $mime = is_array( $img )
                    ? ( $img['mime_type'] ?? 'image/jpeg' )
                    : 'image/jpeg';
                if ( ! $raw ) {
                    pdf2p2_log( sprintf( 'mistral-send.php - Page %s: skipped empty image', $idx ), 'WARNING' );
                    continue;
                }
                if ( strpos( $raw, 'data:' ) !== 0 ) {
                    $clean = preg_replace( '#\s+#', '', $raw );
                    $raw   = 'data:' . $mime . ';base64,' . $clean;
                    pdf2p2_log( sprintf( 'mistral-send.php - Page %s: image converted to base64 %s', $idx, $mime ), 'INFO' );
                }
                $new_content .= '![](' . esc_attr( $raw ) . ")\n\n";
                }
                } else {
                    pdf2p2_log( sprintf( 'mistral-send.php - Page %s: no images found', $idx ), 'INFO' );
                }
}

    if ( $new_content && $new_content !== $post->post_content ) {
        wp_update_post( [
            'ID'           => $post_id,
            'post_content' => wp_slash( $new_content ),
        ] );
    }

    update_post_meta( $post_id, 'mistral_processed', true );
    pdf2p2_log( sprintf( 'mistral-send.php — File %d processed successfully.', $file_url ), 'SUCCESS' );
    return true;
}


function pdf2p2_render_mistral_send_page() {
    echo '<div class="wrap">';

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
    echo '<p>' . esc_html__( 'No documents to process', 'pdf2p2' ) . '</p>';
}

    
    echo '<h1>' . esc_html__( 'Send PDF to Mistral OCR', 'pdf2p2' ) . '</h1>';
    echo '<p>' . esc_html__( 'Enter one or more post IDs (comma-separated):', 'pdf2p2' ) . '</p>';

    echo '<form method="post">';
    wp_nonce_field( 'pdf2p2_send_ocr', 'pdf2p2_send_ocr_nonce' );
    echo '<input type="text" name="send_mistral_post_ids" style="width:300px;" '
       . 'placeholder="e.g. 12,34,56" '
       . 'value="' . ( isset( $_POST['send_mistral_post_ids'] ) 
            ? esc_attr( $_POST['send_mistral_post_ids'] ) 
            : '' ) . '">';
    submit_button( __( 'Send to OCR', 'pdf2p2' ), 'primary', 'send_ocr' );

    echo '</form>';

    if ( ! empty( $_POST['send_ocr'] )
      && check_admin_referer( 'pdf2p2_send_ocr', 'pdf2p2_send_ocr_nonce' )
    ) {
        $raw = sanitize_text_field( wp_unslash( $_POST['send_mistral_post_ids'] ) );
        $ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
        pdf2p2_log( sprintf( 'mistral-send.php — submit clicked. IDs: %s', implode( ', ', $ids ) ), 'INFO' );
        if ( $ids ) {
            echo '<h2>' . esc_html__( 'OCR Results', 'pdf2p2' ) . '</h2>';
            foreach ( $ids as $post_id ) {
                $result = pdf2p2_send_post_to_mistral_ocr( $post_id );
                if ( is_wp_error( $result ) ) {
                    echo '<p style="color:red;"><strong>' 
                       . esc_html( $result->get_error_message() ) 
                       . '</strong></p>';
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

