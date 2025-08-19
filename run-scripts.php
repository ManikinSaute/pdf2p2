<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pdf2p2_render_run_scripts_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    echo '<div class="wrap">';
    echo '<h1>Run PDF2P2 Scripts</h1>';

    if ( ! empty( $_POST ) && check_admin_referer( 'pdf2p2_run_scripts', 'pdf2p2_nonce' ) ) {
        pdf2p2_log( 'run-scripts.php Manual run‐scripts page submitted', 'INFO' );

        if ( isset( $_POST['run_all'] ) ) {
            pdf2p2_log( 'run-scripts.php Button click: RUN ALL', 'INFO' );
            do_action( 'pdf2p2_import_event' );
            pdf2p2_log( 'run-scripts.php RUN ALL: finished import step', 'INFO' );
            do_action( 'pdf2p2_cron_process_unprocessed' );
            pdf2p2_log( 'run-scripts.php RUN ALL: finished OCR step', 'INFO' );
            do_action( 'pdf2p2_cron_process_to_html' );
            pdf2p2_log( 'run-scripts.php RUN ALL: finished HTML step', 'INFO' );
            do_action( 'pdf2p2_cron_process_html_to_gb' );
            pdf2p2_log( 'run-scripts.php RUN ALL: finished Gutenberg step', 'INFO' );
            add_settings_error( 'pdf2p2_messages', 'pdf2p2_all', 'All steps executed', 'updated' );
            pdf2p2_log( 'run-scripts.php RUN ALL: finished all steps', 'SUCCESS' );
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

        if ( isset( $_POST['import_single_pdf'] ) && ! empty( $_POST['pdf_url'] ) ) {
                    $url = esc_url_raw( wp_unslash( $_POST['pdf_url'] ) );
                    pdf2p2_log( "run-scripts.php — Import single PDF: $url", 'INFO' );

                    ob_start();
                    pdf2p2_process_pdf_urls( [ $url ] );
                    $output = ob_get_clean();
                    echo esc_url($output);

                    do_action( 'pdf2p2_cron_process_unprocessed' );
                    do_action( 'pdf2p2_cron_process_to_html' );
                    do_action( 'pdf2p2_cron_process_html_to_gb' );

                    add_settings_error(
                        'pdf2p2_messages',
                        'pdf2p2_single',
                        sprintf( 'Imported and processed PDF: %s', esc_html( $url ) ),
                        'updated'
                    );
                }
            }

    settings_errors( 'pdf2p2_messages' );
    echo '<h2>Trigger Cron Jobs</h2>';
    echo '<p>Use the below buttons to run cons manuly</p>';

    echo '<form method="post" style="margin-bottom:20px;">';
        wp_nonce_field( 'pdf2p2_run_scripts', 'pdf2p2_nonce' );
        submit_button( 'Run All', 'primary', 'run_all' );
        submit_button( 'Run Import Feed', 'secondary', 'run_import' );
        submit_button( 'Process Unprocessed PDFs', 'secondary', 'run_process_unprocessed' );
        submit_button( 'Process MD to HTML', 'secondary', 'run_process_to_html' );
        submit_button( 'Process HTML to Gutenberg', 'secondary', 'run_process_to_gb' );
    echo '</form>';

    echo '<h2>Import a Single PDF by URL</h2>';
    echo '<p>Enter a PDF URL to import and process a single PDF</p>';
    echo '<form method="post">';
        wp_nonce_field( 'pdf2p2_run_scripts', 'pdf2p2_nonce' );
        echo '<input type="url" name="pdf_url" style="width:400px;" placeholder="https://example.com/file.pdf" required />';
        submit_button( 'Import a single file', 'primary', 'import_single_pdf' );
    echo '</form>';

    echo '</div>';
}