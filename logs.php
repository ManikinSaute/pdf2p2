<?php
/**
 * Append a message to our plugin log.
 *
 * @param string $message
 * @param string $level   One of 'INFO', 'WARNING', 'ERROR'
 */
function pdf2p2_log( $message, $level = 'INFO' ) {
    $upload  = wp_upload_dir();
    $log_dir = trailingslashit( $upload['basedir'] ) . 'pdf2p2/logs';
    if ( ! file_exists( $log_dir ) ) {
        wp_mkdir_p( $log_dir );
    }
    $file  = $log_dir . '/plugin.log';
    $ts    = date_i18n( 'Y-m-d H:i:s' );
    $entry = sprintf( "[%s] [%s] %s\n", $ts, strtoupper( $level ), $message );
    error_log( $entry, 3, $file );
}

// Hook PHP warnings/notices into our log
set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
    $lvl = in_array( $errno, [E_WARNING, E_USER_WARNING], true ) ? 'WARNING' : 'ERROR';
    pdf2p2_log( "{$errstr} in {$errfile} on line {$errline}", $lvl );
    return false; // Let WP handle it too if WP_DEBUG is on
} );

/**
 * Render the Tools → PDF2P2 Logs page.
 */
function pdf2p2_render_logs_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $upload   = wp_upload_dir();
    $log_file = trailingslashit( $upload['basedir'] ) . 'pdf2p2/logs/plugin.log';
    // Clear log action
    if ( isset( $_POST['pdf2p2_clear_logs'] ) && check_admin_referer( 'pdf2p2_clear_logs' ) ) {
        file_put_contents( $log_file, '' );
        echo '<div class="notice notice-success"><p>Log file cleared.</p></div>';
    }
    echo '<div class="wrap"><h1>PDF2P2 Logs</h1>';
    if ( ! file_exists( $log_file ) ) {
        echo '<p>No log file found. Nothing has been logged yet.</p>';
    } else {
        $lines = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $tail  = array_slice( $lines, -1000 );

        echo '<form method="post" style="margin-bottom:1em;">'
           . wp_nonce_field( 'pdf2p2_clear_logs', '_wpnonce', true, false )
           . '<input type="submit" name="pdf2p2_clear_logs" class="button button-secondary" value="Clear log">'
           . '</form>';

        echo '<pre style="max-height:600px; overflow:auto; background:#fff; border:1px solid #ddd; padding:1em;"><code>';
        foreach ( $tail as $line ) {
            if ( strpos( $line, '[ERROR]' ) !== false ) {
                echo '<span style="color:#c00;">' . esc_html( $line ) . '</span>' . "\n";
            } elseif ( strpos( $line, '[WARNING]' ) !== false ) {
                echo '<span style="color:#e67e22;">' . esc_html( $line ) . '</span>' . "\n";
            } else {
                echo esc_html( $line ) . "\n";
            }
        }
        echo '</code></pre>';
    }
    echo '</div>';
}

add_action( 'admin_init', function() {
    if ( 1 !== (int) get_option( 'pdf2p2_debug_mode', 0 ) ) {
        return;
    }
    $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
    // only on your settings or logs submenus
    if ( ! in_array( $page, [ 'pdf2p2-settings', 'pdf2p2-logs' ], true ) ) {
        return;
    }
    add_action( 'admin_notices', 'pdf2p2_check_revisions_notice' );
    add_action( 'admin_notices', 'sp_loaded_admin_notice' );
    add_action( 'admin_notices', 'fft_test_fetch_feed' );
    add_action( 'admin_notices', 'check_api_key' );
} );

function pdf2p2_check_revisions_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $rev = WP_POST_REVISIONS;
    if ( $rev === false ) {
        $display = 'disabled';
    } elseif ( is_int( $rev ) ) {
        $display = $rev;
    } else {
        $display = 'default';
    }
    echo '<div class="notice notice-success is-dismissible">Post revisions are currently <em>' . esc_html( $display ) . '</div>';
}

function sp_loaded_admin_notice() {
	    if ( ! class_exists( 'SimplePie\SimplePie', false ) ) {
        require_once ABSPATH . WPINC . '/class-simplepie.php';
    }
    if ( class_exists( 'SimplePie' ) ) {
        echo '<div class="notice notice-success is-dismissible">SimplePie is loaded.</strong> You can safely use fetch_feed() and other SimplePie APIs.</div>';
    } else {
        echo '<div class="notice notice-error is-dismissible">SimplePie is <em>not</em> loaded. RSS parsing functions may not work as expected.</div>';
    }
}

function fft_test_fetch_feed() {
	if ( ! class_exists( 'SimplePie\SimplePie', false ) ) {
    require_once ABSPATH . WPINC . '/class-simplepie.php';
    }
    $test_url = 'https://feeds.bbci.co.uk/news/rss.xml';
    $feed = fetch_feed( $test_url );
    if ( is_wp_error( $feed ) ) {
        echo '<div class="notice notice-error is-dismissible"> Fetch Feed Tester: Error fetching feed: ' . esc_html( $feed->get_error_message() ) . '</div>';
        return;
    }

    $max_items = $feed->get_item_quantity( 1 );
    if ( $max_items > 0 ) {
        echo '<div class="notice notice-success is-dismissible">Fetch Feed Tester: Success!' . esc_html( $max_items ) . ' item(s) retrieved from the feed.</div>';
    } else {
        echo '<div class="notice notice-warning is-dismissible">Fetch Feed Tester: No items found in the feed.</div>';
    }
}

function check_api_key() {
    $api_key = get_option( 'pdf2p2_api_key', '' );
	if ( ! $api_key ) {
        echo '<div class="notice notice-error is-dismissible">API is empty</div>';
    } else {
        echo '<div class="notice notice-success is-dismissible">API key has been added</div>';
    }
    $response = wp_remote_get( 'https://api.mistral.ai/v1/models', 
          [
            'timeout'    => 5,
            'headers'    => [
                'Authorization' => "Bearer {$api_key}",
            ],
            ] );
                if ( is_wp_error( $response ) ) {
                echo '<div class="notice notice-error is-dismissible">API request failed: ' . esc_html( $response->get_error_message() ) . '</div>';
                } elseif ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
                echo '<div class="notice notice-error is-dismissible">API key rejected</div>';
                } else {
                echo '<div class="notice notice-success is-dismissible">API key accepted</div>';
                }
}

