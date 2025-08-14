<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Return an array of PDF URLs in the RSS/Atom feed
 * that have not yet been imported.
 *
 * @param string $feed_url Optional override for the feed URL.
 * @return string[] Array of not-yet-imported PDF URLs.
 */
function pdf2p2_get_not_imported_feed_urls( $feed_url = '' ) {
    if ( empty( $feed_url ) || ! filter_var( $feed_url, FILTER_VALIDATE_URL ) ) {
        $feed_url = 'https://www.amnesty.org/en/latest/feed/';
        pdf2p2_log( 
            sprintf(
                'Feed.php — No valid feed URL set; using default: ' . $feed_url,
                'WARNING'
            )
        );
        } else {
            pdf2p2_log(
                'Feed.php — Using configured feed URL: ' . $feed_url,
                'INFO'
            );
        }

    $response = wp_remote_get( $feed_url );
    if ( is_wp_error( $response ) ) {
            pdf2p2_log(
                'Feed.php — HTTP request failed: ' . $response->get_error_message(),
                'ERROR'
            );
            return [];
    }

    $body = wp_remote_retrieve_body( $response );
    libxml_use_internal_errors( true );
    $xml  = simplexml_load_string( $body );
    if ( false === $xml ) {
        return [];
    }

    if ( isset( $xml->channel->item ) && count( $xml->channel->item ) ) {
        $items = $xml->channel->item;
    } elseif ( isset( $xml->entry ) && count( $xml->entry ) ) {
        $items = $xml->entry;
    } else {
        return [];
    }

    $posts = get_posts( [
        'post_type'      => [ 'pdf2p2_import', 'pdf2p2_gutenberg' ],
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_query'     => [[ 'key' => 'pdf2p2_original_file_path', 'compare' => 'EXISTS' ]],
    ] );
    $imported = [];
    foreach ( $posts as $p ) {
        $path = get_post_meta( $p->ID, 'pdf2p2_original_file_path', true );
        if ( $path ) {
            $imported[] = esc_url_raw( $path );
        }
    }
    $imported = array_unique( $imported );
    pdf2p2_log(
        'Feed.php — Found ' . count( $imported ) . ' already-imported PDFs',
        'INFO'
    );

    $feed_paths = [];
    foreach ( $items as $item ) {
        if ( isset( $item->guid ) ) {
            $u = (string) $item->guid;
        } elseif ( isset( $item->link['href'] ) ) {
            $u = (string) $item->link['href'];
        } elseif ( isset( $item->link ) ) {
            $u = (string) $item->link;
        } else {
            continue;
        }

        if ( stripos( $u, '.pdf' ) !== false ) {
            $feed_paths[] = esc_url_raw( $u );
        }
    }
    $feed_paths = array_unique( $feed_paths );
        pdf2p2_log(
            'Feed.php — Found ' . count( $feed_paths ) . ' total PDFs in feed',
            'INFO'
        );

    $not_imported = array_diff( $feed_paths, $imported );
    pdf2p2_log(
        'Feed.php — Returning ' . count( $not_imported ) . ' not-yet-imported PDFs',
        'INFO'
    );
    return $not_imported;
}

function pdf2p2_render_rss_feed() {
    $feed_url = trim( get_option( 'pdf2p2_import_rssfeed_url', '' ) );
    if ( empty( $feed_url ) || ! filter_var( $feed_url, FILTER_VALIDATE_URL ) ) {
        $feed_url = 'https://www.amnesty.org/en/latest/feed/';
    }

    $response = wp_remote_get( $feed_url );
    if ( is_wp_error( $response ) ) {
        echo '<div class="notice notice-error"><p>Request failed: '
           . esc_html( $response->get_error_message() )
           . '</p></div>';
        return;
    }

    $body = wp_remote_retrieve_body( $response );
    libxml_use_internal_errors( true );
    $xml = simplexml_load_string( $body );
    if ( false === $xml ) {
        echo '<div class="notice notice-warning"><p>Failed to parse XML.</p></div>';
        return;
    }

    if ( isset( $xml->channel->item ) && count( $xml->channel->item ) ) {
        $items = $xml->channel->item;
    } elseif ( isset( $xml->entry ) && count( $xml->entry ) ) {
        $items = $xml->entry;
    } else {
        echo '<div class="notice notice-warning"><p>No feed items found.</p></div>';
        return;
    }

    $imported_posts = get_posts( [
        'post_type'      => [ 'pdf2p2_import', 'pdf2p2_gutenberg' ],
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_query'     => [[
            'key'     => 'pdf2p2_original_file_path',
            'compare' => 'EXISTS',
        ]],
    ] );

    $imported_paths = [];
    foreach ( $imported_posts as $post ) {
        $path = get_post_meta( $post->ID, 'pdf2p2_original_file_path', true );
        if ( $path ) {
            $imported_paths[] = esc_url_raw( $path );
        }
    }
    $imported_paths = array_unique( $imported_paths );

    $feed_paths = [];
    foreach ( $items as $item ) {
        if ( isset( $item->guid ) ) {
            $url = (string) $item->guid;
        } elseif ( isset( $item->link['href'] ) ) {
            $url = (string) $item->link['href'];
        } elseif ( isset( $item->link ) ) {
            $url = (string) $item->link;
        } else {
            continue;
        }
        $url = esc_url_raw( $url );
        if ( false !== stripos( $url, '.pdf' ) ) {
            $feed_paths[] = $url;
        }
    }
    $feed_paths = array_unique( $feed_paths );
    $not_imported = array_diff( $feed_paths, $imported_paths );
    echo '<div class="wrap">';
    echo '<h1>RSS/Atom Feed</h1>';
    echo '<p><strong>Feed URL:</strong> '
       . '<a href="' . esc_url( $feed_url ) . '" target="_blank">'
       . esc_html( $feed_url ) . '</a></p>';
    echo '<h2>All PDF File Paths in Feed</h2>';
    if ( $feed_paths ) {
        echo '<ul style="list-style:disc inside;">';
        foreach ( $feed_paths as $url ) {
            echo '<li><code>' . esc_html( $url ) . '</code></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No PDF URLs found in feed.</p>';
    }
    echo '<h2 style="margin-top:2em;">Already Imported PDFs</h2>';
    if ( $imported_paths ) {
        echo '<ul style="list-style:disc inside;">';
        foreach ( $imported_posts as $post ) {
            $path = get_post_meta( $post->ID, 'pdf2p2_original_file_path', true );
            if ( $path ) {
                $url = esc_url_raw( $path );
                $title = get_the_title( $post->ID );
                $post_type = get_post_type( $post->ID );
                echo '<li><code>' . esc_html( $url ) . '</code>';
                echo ' &mdash; <strong>' . esc_html( $title ) . '</strong>';
                echo ' <em>(' . esc_html( $post_type ) . ')</em>';
                echo '</li>';
                // echo '<pre>'; var_dump( $imported_posts[0] ); echo '</pre>';
            } else {
                echo '<li><code>' . esc_html( $post->ID ) . '</code> (no path found)</li>';
            }
        }
        echo '</ul>';
    } else {
        echo '<p>No files have been imported yet.</p>';
    }
    echo '<h2 style="margin-top:2em;">Feed PDFs Not Yet Imported</h2>';
    if ( $not_imported ) {
        echo '<ul style="list-style:disc inside;">';
        foreach ( $not_imported as $url ) {
            echo '<li><code>' . esc_html( $url ) . '</code></li>';
        }
    } else {
        echo '<p>All feed PDFs have already been imported.</p>';
    }
    echo '</div>';
}