<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pdf2p2_render_run_scripts_page() {
    echo '<div class="wrap">';
    echo '<h1>Run PDF2P2 Scripts</h1>';
    if ( ! empty( $_POST ) && check_admin_referer( 'pdf2p2_run_scripts', 'pdf2p2_nonce' ) ) {
        pdf2p2_log( 'Manual run‐scripts page submitted', 'INFO' );
        if ( isset( $_POST['run_import'] ) ) {
            pdf2p2_log( 'Button click: import feed', 'INFO' );
            do_action( 'pdf2p2_import_event' );
            add_settings_error( 'pdf2p2_messages', 'pdf2p2_imported', 'Import event executed.', 'updated' );
        }
        if ( isset( $_POST['run_process_unprocessed'] ) ) {
            pdf2p2_log( 'Button click: process', 'INFO' );
            do_action( 'pdf2p2_cron_process_unprocessed' );
            add_settings_error( 'pdf2p2_messages', 'pdf2p2_processed', 'Process unprocessed docs executed.', 'updated' );
        }
        if ( isset( $_POST['run_move_to_gb'] ) ) {
            pdf2p2_log( 'Button click: move to gb', 'INFO' );
            do_action( 'pdf2p2_cron_move_post_to_gutenberg' );
            add_settings_error( 'pdf2p2_messages', 'pdf2p2_moved', 'Move to Gutenberg executed.', 'updated' );
        }
    }

    settings_errors( 'pdf2p2_messages' );
    echo '<form method="post" style="margin-bottom:20px;">';
        wp_nonce_field( 'pdf2p2_run_scripts', 'pdf2p2_nonce' );
        submit_button( 'Run Import Feed', 'primary', 'run_import' );
    echo '</form>';

    echo '<form method="post" style="margin-bottom:20px;">';
        wp_nonce_field( 'pdf2p2_run_scripts', 'pdf2p2_nonce' );
        submit_button( 'Process Unprocessed PDFs', 'primary', 'run_process_unprocessed' );
    echo '</form>';

    echo '<form method="post">';
        wp_nonce_field( 'pdf2p2_run_scripts', 'pdf2p2_nonce' );
        submit_button( 'Move Posts to Gutenberg', 'primary', 'run_move_to_gb' );
    echo '</form>';

    echo '</div>';
}