<?php

function pdf2p2_admin_menu() {
    add_menu_page(
        'pdf2p&sup2;',               
        'pdf2p&sup2; Home',               
        'manage_options',       
        'pdf2p2-home',          
        'render_pdf2p2_home_page',
        'dashicons-media-document'
    );

    $subs = [
        ['slug' => 'pdf2p2-settings',       'title' => 'Settings',            'callback' => 'pdf2p2_render_settings_page'],
        ['slug' => 'pdf2p2-rss-feed',       'title' => 'PDF Feed',                'callback' => 'pdf2p2_render_rss_feed'],
        ['slug' => 'pdf2p2-import',         'title' => 'PDF Import',              'callback' => 'pdf2p2_render_import_page'],
        ['slug' => 'pdf2p2-mistral',        'title' => 'Check Post',            'callback' => 'pdf2p2_render_mistral_page'],
        ['slug' => 'pdf2p2-mistral-send',   'title' => 'Process PDF to OCR',       'callback' => 'pdf2p2_render_mistral_send_page'], 
        ['slug' => 'pdf2p2-html-processing',   'title' => 'Process MD to HTML',        'callback' => 'pdf2p2_render_html_page'],
        ['slug' => 'pdf2p2-gutenberg-processing',   'title' => 'Process HTML to GB',        'callback' => 'pdf2p2_render_gb_page'],
   //     ['slug' => 'pdf2p2-md-gb',          'title' => 'Move to Gutenberg TODO',              'callback' => 'pdf2p2_render_md_gb_page'],
        ['slug' => 'pdf2p2-run-scripts',    'title' => 'Run Scripts',         'callback' => 'pdf2p2_render_run_scripts_page'],
        ['slug' => 'pdf2p2-logs',           'title' => 'Logs TO DO',                'callback' => 'pdf2p2_render_logs_page'],
        ['slug' => 'pdf2p2-import-w-ocr',   'title' => 'Import w OCR OLD',        'callback' => 'pdf2p2_render_import_ocr_page'],
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