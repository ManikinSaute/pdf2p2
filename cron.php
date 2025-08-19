<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

register_activation_hook( __FILE__, 'pdf2p2_activate' );
function pdf2p2_activate() {
    pdf2p2_log( 'cron.php — Activation hook triggered' , 'INFO' );
    $schedule = get_option( 'pdf2p2_cron_schedule', 'daily' );
    if ( ! wp_next_scheduled( 'pdf2p2_import_event' ) ) {
        wp_schedule_event( time(), $schedule, 'pdf2p2_import_event' );
    }
    if ( ! wp_next_scheduled( 'pdf2p2_cron_process_unprocessed' ) ) {
        wp_schedule_event( time(), $schedule, 'pdf2p2_cron_process_unprocessed' );
    }
    if ( ! wp_next_scheduled( 'pdf2p2_cron_process_to_html' ) ) {
        wp_schedule_event( time(), $schedule, 'pdf2p2_cron_process_to_html' );
    }
    if ( ! wp_next_scheduled( 'pdf2p2_cron_process_html_to_gb' ) ) {
        wp_schedule_event( time(), $schedule, 'pdf2p2_cron_process_html_to_gb' );
    }
    pdf2p2_log( 'cron.php — Activation hook completed' , 'INFO' );
}

register_deactivation_hook( __FILE__, 'pdf2p2_deactivate' );
function pdf2p2_deactivate() {
    wp_clear_scheduled_hook( 'pdf2p2_import_event' );
    wp_clear_scheduled_hook( 'pdf2p2_cron_process_unprocessed' );
    wp_clear_scheduled_hook( 'pdf2p2_cron_process_to_html' );
    wp_clear_scheduled_hook( 'pdf2p2_cron_process_html_to_gb' );
    pdf2p2_log( 'cron.php — Deactivation hook completed' , 'INFO' );
}

add_action( 'update_option_pdf2p2_cron_schedule', 'pdf2p2_reschedule', 10, 2 );
function pdf2p2_reschedule( $old, $new ) {
    if ( $old === $new ) {
        return;
    }
    wp_clear_scheduled_hook( 'pdf2p2_import_event' );
    wp_clear_scheduled_hook( 'pdf2p2_cron_process_unprocessed' );
    wp_clear_scheduled_hook( 'pdf2p2_cron_process_to_html' );
    wp_clear_scheduled_hook( 'pdf2p2_cron_process_html_to_gb' );    
    $schedules = wp_get_schedules();
    if ( isset( $schedules[ $new ] ) ) {
        wp_schedule_event( time(), $new, 'pdf2p2_import_event' );
        wp_schedule_event( time(), $new, 'pdf2p2_cron_process_unprocessed' );
        wp_schedule_event( time(), $new, 'pdf2p2_cron_process_to_html' );
        wp_schedule_event( time(), $new, 'pdf2p2_cron_process_html_to_gb' );
        pdf2p2_log( 'cron.php — Change - New triggered' , 'INFO' );
    }
}

add_action( 'pdf2p2_import_event', 'pdf2p2_cron_import_event' );
function pdf2p2_cron_import_event() {
    $feed_url = get_option( 'pdf2p2_import_rssfeed_url', '' );
    $urls     = pdf2p2_get_not_imported_feed_urls( $feed_url );
    if ( ! empty( $urls ) ) {
        pdf2p2_process_pdf_urls( $urls, false );
        pdf2p2_log( sprintf( 'cron.php — Import triggered "%s".', implode( ',', (array) $urls ) ), 'INFO' );
    }
    if (empty( $feed_url ) ) {
        pdf2p2_log( sprintf( 'cron.php — No RSS feed URL configured (option was "%s"); import skipped.', $feed_url ), 'ERROR'  );
    }
}

add_action( 'pdf2p2_cron_process_unprocessed', 'pdf2p2_cron_process_unprocessed' );
function pdf2p2_cron_process_unprocessed() {
    $ids = pdf2p2_get_unprocessed_post_ids();
    if ( empty( $ids ) ) {
        pdf2p2_log( 'cron.php - No unprocessed docs found', 'INFO' );
        return;
    }
    foreach ( $ids as $post_id ) {
        $result = pdf2p2_send_post_to_mistral_ocr( $post_id );
            pdf2p2_log( sprintf( 'cron.php - post processed by OCR (ID: %d)', $post_id ), 'INFO' );
        if ( is_wp_error( $result ) ) {
            pdf2p2_log( sprintf( 'PDF2P2 OCR error for post %d: ', $post_id ), 'ERROR' );
        }
    }
}

add_action( 'pdf2p2_cron_process_to_html', 'pdf2p2_cron_process_to_html' );
function pdf2p2_cron_process_to_html() {
    $candidates = pdf2p2_get_html_unprocessed_ids();
    if ( empty( $candidates ) ) {
        pdf2p2_log( 'cron.php - No HTML candidates found', 'INFO' );
        return;
    }
    foreach ( $candidates as $post_id ) {
        $result = pdf2p2_process_to_html( $post_id );
        if ( is_wp_error( $result ) ) {
            pdf2p2_log( sprintf( 'cron.php - Error processing post %d: %s', $post_id, $result->get_error_message() ), 'ERROR' );
        } else {
        pdf2p2_log( sprintf( 'cron.php - Post processed to HTML (ID: %d)', $post_id ), 'INFO' );
        }
    }
}

 add_action( 'pdf2p2_cron_process_html_to_gb', 'pdf2p2_cron_process_html_to_gb' );
 function pdf2p2_cron_process_html_to_gb() {
    $candidates = pdf2p2_get_gb_unprocessed_post_ids();
    if ( empty( $candidates ) ) {
        pdf2p2_log( 'cron.php - No gb candidates found to process', 'INFO' );
        return;
    }
    foreach ( $candidates as $post_id ) {
        $result = pdf2p2_process_html_to_gb( $post_id );
        if ( $result === false ) {
            pdf2p2_log( sprintf( 'cron.php — GB conversion failed (ID: %d)', $post_id ), 'ERROR' );
        } else {
            pdf2p2_log( sprintf( 'cron.php — Post processed to GB (ID: %d)', $post_id ), 'INFO' );
        }
    }
}