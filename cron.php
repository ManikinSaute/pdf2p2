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
    if ( ! wp_next_scheduled( 'pdf2p2_cron_move_post_to_gutenberg' ) ) {
        wp_schedule_event( time(), $schedule, 'pdf2p2_cron_move_post_to_gutenberg' );
    }
}

register_deactivation_hook( __FILE__, 'pdf2p2_deactivate' );
function pdf2p2_deactivate() {
    wp_clear_scheduled_hook( 'pdf2p2_import_event' );
    wp_clear_scheduled_hook( 'pdf2p2_cron_process_unprocessed' );
    wp_clear_scheduled_hook( 'pdf2p2_cron_move_post_to_gutenberg' );
    pdf2p2_log( 'cron.php — Deactivation hook triggered' , 'INFO' );
}

add_action( 'update_option_pdf2p2_cron_schedule', 'pdf2p2_reschedule', 10, 2 );
function pdf2p2_reschedule( $old, $new ) {
    if ( $old === $new ) {
        return;
    }
    wp_clear_scheduled_hook( 'pdf2p2_import_event' );
    wp_clear_scheduled_hook( 'pdf2p2_cron_process_unprocessed' );
    wp_clear_scheduled_hook( 'pdf2p2_cron_move_post_to_gutenberg' );
    pdf2p2_log( 'cron.php — Change - Clear triggered' , 'INFO' );

    $schedules = wp_get_schedules();
    if ( isset( $schedules[ $new ] ) ) {
        wp_schedule_event( time(), $new, 'pdf2p2_import_event' );
        wp_schedule_event( time(), $new, 'pdf2p2_cron_process_unprocessed' );
        wp_schedule_event( time(), $new, 'pdf2p2_cron_move_post_to_gutenberg' );
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
        pdf2p2_log( sprintf( 'cron.php - post processed by OCR "%s"); .', $post_id ), 'INFO'  );
        if ( is_wp_error( $result ) ) {
            error_log( sprintf( 'PDF2P2 OCR error for post %d: %s', $post_id, $result->get_error_message() ), 'INFO' );
        }
    }
}

add_action( 'pdf2p2_cron_move_post_to_gutenberg', 'pdf2p2_cron_move_post_to_gutenberg' );
function pdf2p2_cron_move_post_to_gutenberg() {
    $candidates = pdf2p2_get_gutenberg_candidates();
    if ( empty( $candidates ) ) {
        pdf2p2_log( 'cron.php - No Gutenberg candidates found', 'INFO' );
        return;
    }
    foreach ( $candidates as $post_id ) {
        pdf2p2_move_post_to_gutenberg( $post_id );
        pdf2p2_log( sprintf( 'cron.php — Post moved to GB "%s"); .', $post_id ), 'INFO'  );
    }
}