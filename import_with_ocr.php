<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*


// I think this file is no longer needed, as the functionality has been moved to import_with_ocr.php

function pdf2p2_process_and_ocr_pdf_urls( array $urls, $force = false ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $api_key = get_option( 'pdf2p2_api_key', '' );

    foreach ( $urls as $pdf_url ) {
        // … your download / sideload / OCR / update loop here …
        update_post_meta( $post_id, 'pdf2p2_original_file_path', $pdf_url );
        // etc.
    }
}

function pdf2p2_render_import_ocr_page() {
    ?>
    <div class="wrap">
      <h1><?php esc_html_e( 'Import & OCR PDFs', 'pdf2p2' ); ?></h1>
      <p><?php esc_html_e( 'Enter one or more PDF URLs (one per line):', 'pdf2p2' ); ?></p>

      <?
        $feed_url     = get_option( 'pdf2p2_import_rssfeed_url' );
        $not_imported = pdf2p2_get_not_imported_feed_urls( $feed_url );

        // $not_imported is now a simple array of strings (the URLs)
        foreach ( $not_imported as $pdf_url ) {
            echo esc_html( $pdf_url ) . '<br>';
            } ?>


      <form method="post">
        <?php wp_nonce_field( 'pdf2p2_import_ocr', 'pdf2p2_import_ocr_nonce' ); ?>
        <p>
          <textarea name="pdf_urls" rows="5" style="width:100%;"
            placeholder="https://example.com/file1.pdf&#10;https://example.com/file2.pdf"
            required><?php
              echo isset( $_POST['pdf_urls'] )
                ? esc_textarea( wp_unslash( $_POST['pdf_urls'] ) )
                : '';
            ?></textarea>
        </p>
        <p>
          <button type="submit" name="process_pdfs" class="button button-primary">
            <?php esc_html_e( 'Import & OCR PDFs', 'pdf2p2' ); ?>
          </button>
        </p>
      </form>
    <?php
    if ( ! empty( $_POST['process_pdfs'] )
      && wp_verify_nonce( $_POST['pdf2p2_import_ocr_nonce'], 'pdf2p2_import_ocr' )
    ) {
        // 1) Grab & sanitize lines
        $raw   = sanitize_textarea_field( wp_unslash( $_POST['pdf_urls'] ) );
        $lines = preg_split( '/\r\n|\r|\n/', trim( $raw ) );
        $urls  = array_filter( array_map( 'esc_url_raw', $lines ) );

        if ( $urls ) {
            // 2) Call your combined import+OCR routine
            pdf2p2_process_and_ocr_pdf_urls( $urls );
        } else {
            echo '<div class="notice notice-warning"><p>'
               . esc_html__( 'No valid PDF URLs provided.', 'pdf2p2' )
               . '</p></div>';
        }
    }
    echo '</div>';
}

*/
