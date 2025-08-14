<?php
/*
Plugin Name: pdf2p2
Description: PDF import with OCR 
Version:     1.7
Author:      Thomas Parsons
Requires at least: 6.7
Tested up to:      6.7
Requires PHP:      8.2
Author URI:  https://github.com/ManikinSaute/pdf2p2
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// core files
require_once ABSPATH . WPINC . '/feed.php';
require_once ABSPATH . WPINC . '/class-simplepie.php';
// plugin file 
require_once __DIR__ . '/menu.php';
require_once __DIR__ . '/cpt-terms.php';
require_once __DIR__ . '/import.php';
require_once __DIR__ . '/logs.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/feed.php';
require_once __DIR__ . '/cron.php';
require_once __DIR__ . '/pdf2p2-home.php'; 
require_once __DIR__ . '/md-gb.php';
require_once __DIR__ . '/default-content.php';
require_once __DIR__ . '/mistral.php';
require_once __DIR__ . '/mistral-send.php';
require_once __DIR__ . '/pdf2p2-templates.php';
require_once __DIR__ . '/import_with_ocr.php';
require_once __DIR__ . '/run-scripts.php';
register_activation_hook( __FILE__, 'pdf2p2_activate' );

add_action( 'init', function() {
    if ( isset( $_GET['run_pdf2p2_cron'] ) ) {
        do_action( 'pdf2p2_cron_process_unprocessed' );
        wp_die( '📦 OCR cron manually triggered' );
    }
});