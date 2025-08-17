<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pdf2p2_render_run_scripts_page() {
    echo '<div class="wrap">';
    echo '<h1>Run PDF2P2 Scripts</h1>';

    if ( ! empty( $_POST ) && check_admin_referer( 'pdf2p2_run_scripts', 'pdf2p2_nonce' ) ) {
        pdf2p2_log( 'Manual run‐scripts page submitted', 'INFO' );

        if ( isset( $_POST['run_all'] ) ) {
            pdf2p2_log( 'run-scripts.php Button click: RUN ALL', 'INFO' );
            do_action( 'pdf2p2_import_event' );
            pdf2p2_log( 'run-scripts.php RUN ALL: finished import step', 'INFO' );
            do_action( 'pdf2p2_cron_process_unprocessed' );
            pdf2p2_log( 'run-scripts.php RUN ALL: finished import step', 'INFO' );
            do_action( 'run-scripts.php pdf2p2_cron_process_to_html' );
            pdf2p2_log( 'run-scripts.php RUN ALL: finished HTML step', 'INFO' );
            do_action( 'run-scripts.php pdf2p2_cron_process_html_to_gb' );
            pdf2p2_log( 'RUN ALL: finished GB step', 'INFO' );
            add_settings_error( 'pdf2p2_messages', 'pdf2p2_all', 'All steps executed: Import → Process PDFs → MD→HTML → HTML→Gutenberg.', 'updated' );
        }

        if ( isset( $_POST['run_import'] ) ) {
            pdf2p2_log( 'run-scripts.php Button click: import feed', 'INFO' );
            do_action( 'pdf2p2_import_event' );
            add_settings_error( 'pdf2p2_messages', 'pdf2p2_imported', 'Import event executed.', 'updated' );
        }

        if ( isset( $_POST['run_process_unprocessed'] ) ) {
            pdf2p2_log( 'run-scripts.php Button click: process', 'INFO' );
            do_action( 'pdf2p2_cron_process_unprocessed' );
            add_settings_error( 'pdf2p2_messages', 'pdf2p2_processed', 'Processed unprocessed docs executed.', 'updated' );
        }

        if ( isset( $_POST['run_process_to_html'] ) ) {
            pdf2p2_log( 'run-scripts.php Button click: move to html', 'INFO' );
            do_action( 'pdf2p2_cron_process_to_html' );
            add_settings_error( 'pdf2p2_messages', 'pdf2p2_html', 'Processed to HTML executed.', 'updated' );
        }

        if ( isset( $_POST['run_process_to_gb'] ) ) {
            pdf2p2_log( 'run-scripts.php Button click: move to gb', 'INFO' );
            do_action( 'pdf2p2_cron_process_html_to_gb' );
            add_settings_error( 'pdf2p2_messages', 'pdf2p2_gb', 'Processed to Gutenberg executed.', 'updated' );
        }
    }

    settings_errors( 'pdf2p2_messages' );

    echo '<form method="post" style="margin-bottom:20px;">';
        wp_nonce_field( 'pdf2p2_run_scripts', 'pdf2p2_nonce' );
        submit_button( 'Run All (Import → Process → HTML → Gutenberg)', 'primary', 'run_all' );
    echo '</form>';

    echo '<form method="post" style="margin-bottom:20px;">';
        wp_nonce_field( 'pdf2p2_run_scripts', 'pdf2p2_nonce' );
        submit_button( 'Run Import Feed', 'secondary', 'run_import' );
    echo '</form>';

    echo '<form method="post" style="margin-bottom:20px;">';
        wp_nonce_field( 'pdf2p2_run_scripts', 'pdf2p2_nonce' );
        submit_button( 'Process Unprocessed PDFs', 'secondary', 'run_process_unprocessed' );
    echo '</form>';

    echo '<form method="post" style="margin-bottom:20px;">';
        wp_nonce_field( 'pdf2p2_run_scripts', 'pdf2p2_nonce' );
        submit_button( 'Process MD to HTML', 'secondary', 'run_process_to_html' );
    echo '</form>';

    echo '<form method="post">';
        wp_nonce_field( 'pdf2p2_run_scripts', 'pdf2p2_nonce' );
        submit_button( 'Process HTML to Gutenberg', 'secondary', 'run_process_to_gb' );
    echo '</form>';

    echo '</div>';
}