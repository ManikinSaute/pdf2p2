<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Process an array of PDF URLs: download, sideload, hash, create import post.
 *
 * @param string[] $urls
 * @param bool     $force Skip duplicate check when true.
 */

// TO DO split the ID look up from the processing so we can imporve performance, this pull the whole post
// TO DO make the ID look up a class method so we can use it in other places
// TO DO look at caching 
// TO DO Look at custom look up table 
// TO DO look at using WP-CLI for batch processing
// TO DO look at using a look up that returns if one of the checks is true beofre doing the next check, like is does the file name exist, no, then does the URL exist, no, then does the hash exist etc. might be the wrong order but it should be considered. ie try and exit the loop early if possible


function pdf2p2_process_pdf_urls( array $urls, $force = false ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    pdf2p2_log( 'Import.php — batch started ', 'INFO' );
    foreach ( $urls as $pdf_url ) {
        $file_name = wp_basename( $pdf_url );
        pdf2p2_log( sprintf( 'Processing URL: %s', $pdf_url ), 'INFO' );
        $existing = get_posts( [
            'post_type'   => 'pdf2p2_import',
            'numberposts' => 1,
            'meta_query'  => [
                'relation' => 'OR',
                [ 'key' => 'pdf2p2_original_file_path', 'value' => $pdf_url ],
                [ 'key' => 'pdf2p2_file_name',          'value' => $file_name ],
            ],
        ] );
        if ( $existing && ! $force ) {
            pdf2p2_log( 
                sprintf( 'import.php — skipped already exists: %s', $file_name ), 
                'INFO' 
            );
            echo '<div class="notice notice-warning"><p>' .
                sprintf(
                    'Skipped import for %s: already exists.',
                    esc_html( $file_name )
                ) .
                '</p></div>';

            continue;
        }

        $tmp_file = download_url( $pdf_url );
        if ( is_wp_error( $tmp_file ) ) {
            pdf2p2_log(
                sprintf( 'import.php — wp error : %s', $pdf_url ),
                'ERROR'
            );
            echo '<div class="notice notice-error"><p>' .
                sprintf(
                    'Error downloading %s: %s',
                    esc_html( $file_name ),
                    esc_html( $tmp_file->get_error_message() )
                ) .
                '</p></div>';
            continue;
        }

        $file_array = [ 'name' => $file_name, 'tmp_name' => $tmp_file ];
        $attach_id  = media_handle_sideload( $file_array, 0 );
        if ( is_wp_error( $attach_id ) ) {
            pdf2p2_log(
                sprintf( 'import.php — wp error side load : %s', $pdf_url ),
                'ERROR'
            );
            wp_delete_file( $file_array['tmp_name'] );
            echo '<div class="notice notice-error"><p>' .
                sprintf(
                    'Upload error for %s: %s',
                    esc_html( $file_name ),
                    esc_html( $attach_id->get_error_message() )
                ) .
                '</p></div>';
            continue;
        }

        $file_path  = get_attached_file( $attach_id );
        $file_hash  = hash_file( 'sha256', $file_path );
        $attach_url = wp_get_attachment_url( $attach_id );
        $md_file = plugin_dir_path( __FILE__ ) . 'md-example.txt';

        if ( file_exists( $md_file ) ) {
            $content = file_get_contents( $md_file );
        } else {
            $content = 'OCR content';
            pdf2p2_log( 'Import.php — md file not found ', 'WARNING' );
        }

        $post_id = wp_insert_post( [
            'post_title'   => $file_name,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'pdf2p2_import',
        ] );

        if ( ! is_wp_error( $post_id ) ) {
                pdf2p2_log( 
                    sprintf( 'import.php — all good in the hood : %s', $file_name ), 
                    'SUCCESS' 
                    );
            wp_set_object_terms( $post_id, 'un_verified', 'status', true );
            update_post_meta( $post_id, 'pdf2p2_original_file_path', $pdf_url );
            update_post_meta( $post_id, 'pdf2p2_new_file_url',      $attach_url );
            update_post_meta( $post_id, 'pdf2p2_file_path',         $file_path );
            update_post_meta( $post_id, 'pdf2p2_attachment_id',     $attach_id );
            update_post_meta( $post_id, 'pdf2p2_file_hash',         $file_hash );
            update_post_meta( $post_id, 'pdf2p2_file_name',         $file_name );
            update_post_meta( $post_id, 'mistral_processed',       '0' );

            echo '<div class="notice notice-success"><p>'
               . sprintf(
                   'Imported %s (Post ID: %d)',
                   esc_html( $file_name ),
                   esc_html( $post_id )
               )
               . '</p></div>';
        } else {
            pdf2p2_log( 
                sprintf( 'import.php — wp error creating post: %s', $file_name ), 
                'ERROR' 
                );
            echo '<div class="notice notice-error"><p>'
               . sprintf(
                   'Error creating import post for %s: %s',
                   esc_html( $file_name ),
                   esc_html( $post_id->get_error_message() )
               )
               . '</p></div>';
               pdf2p2_log( 'Import.php — file processing error ', 'ERROR' );
        }
    }
}

function pdf2p2_render_import_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    ?>
    <div class="wrap">
      <h1>Import PDFs</h1>
      <p>This page allows you to import PDF files from a list of URLs.</p>
      <h2>Unimported</h2>
      <p>Any unimported files will that are in your feed will be listed below.</p>
            <?php
        $feed_url     = get_option( 'pdf2p2_import_rssfeed_url' );
        $not_imported = pdf2p2_get_not_imported_feed_urls( $feed_url );
        foreach ( $not_imported as $pdf_url ) {
            echo esc_html( $pdf_url ) . '<br>';
            } ?>


      <h2>Import PDFs</h2>
      <p>Enter one or more PDF URLs (one per line) this will create a post and sideload the PDF into the media library and also compute a SHA-256 hash for each file.</p>
      
      <form method="post">
        <?php wp_nonce_field( 'pdf2p2_import', 'pdf2p2_import_nonce' ); 
        $prefill = 'https://www.amnesty.org/en/wp-content/uploads/2025/08/ACT5001972025ENGLISH.pdf';
        if ( isset( $_POST['pdf2p2_import'] ) ) {
            $prefill = esc_textarea(
                sanitize_textarea_field( wp_unslash( $_POST['pdf2p2_import'] ) )
            );
        }
	    ?>
        <textarea name="pdf2p2_import" rows="5" style="width:800px;" required><?php echo esc_url( $prefill ); ?></textarea>
            <p>
                <label>
                    <input type="checkbox" name="force_import" value="1"
                        <?php checked( ! empty( $_POST['force_import'] ) ); ?>>
                    Force import even if duplicates are found
                </label>
            </p>
            <input type="submit" name="pdf2p2_import_submit" class="button button-primary" value="Upload PDFs">
        </form>
    <?php

    if ( isset( $_POST['pdf2p2_import_submit'], $_POST['pdf2p2_import_nonce'] ) 
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pdf2p2_import_nonce'] ) ), 'pdf2p2_import' ) ) {
        $raw   = sanitize_textarea_field( wp_unslash( $_POST['pdf2p2_import'] ) );
        $lines = preg_split( '/\r\n|\r|\n/', trim( $raw ) );
        $urls  = array_filter( array_map( 'esc_url_raw', $lines ) );
        $force = ! empty( $_POST['force_import'] ) ? (bool) absint( $_POST['force_import'] ) : false;
        pdf2p2_process_pdf_urls( $urls, $force );
        pdf2p2_log( 
            sprintf( 'Import.php — submitted with URLs: %s', implode( ', ', $urls ) ), 
            'INFO' 
            );
    }
    echo '</div>';
}
