<?php

function pdf2p2_admin_menu() {
    add_menu_page(
        'pdf2p&sup2;',               
        'pdf2p&sup2;',               
        'manage_options',       
        'pdf2p2-home',          
        'render_pdf2p2_home_page',
        'dashicons-media-document'
    );

    $subs = [
        ['slug' => 'pdf2p2-rss-feed',       'title' => 'pdf2p&sup2; Feed',                'callback' => 'pdf2p2_render_rss_feed'],
        ['slug' => 'pdf2p2-import-w-ocr',   'title' => 'pdf2p&sup2; Import w OCR',        'callback' => 'pdf2p2_render_import_ocr_page'],
        ['slug' => 'pdf2p2-md-gb',          'title' => 'pdf2p&sup2; md&sup2;gb',              'callback' => 'pdf2p2_render_md_gb_page'],
        ['slug' => 'pdf2p2-import',         'title' => 'pdf2p&sup2; Import',              'callback' => 'pdf2p2_render_import_page'],
        ['slug' => 'pdf2p2-mistral',        'title' => 'pdf2p&sup2; Mistral',            'callback' => 'pdf2p2_render_mistral_page'],
        ['slug' => 'pdf2p2-mistral-send',   'title' => 'pdf2p&sup2; Mistral Send',       'callback' => 'pdf2p2_render_mistral_send_page'], 
        ['slug' => 'pdf2p2-settings',       'title' => 'pdf2p&sup2; Settings',            'callback' => 'pdf2p2_render_settings_page'],
        ['slug' => 'pdf2p2-logs',           'title' => 'pdf2p&sup2; Logs',                'callback' => 'pdf2p2_render_logs_page'],
        ['slug' => 'pdf2p2-run-scripts',    'title' => 'pdf2p&sup2; Run Scripts',         'callback' => 'pdf2p2_render_run_scripts_page'],
    ];

    foreach ( $subs as $sub ) {
        add_submenu_page(
            'pdf2p2-home',
            $sub['title'],      
            $sub['title'],      
            'manage_options',
            $sub['slug'],
            $sub['callback']
        );
    }
}
add_action( 'admin_menu', 'pdf2p2_admin_menu' );