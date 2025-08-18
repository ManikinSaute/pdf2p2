<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// If I came back to this I would make a class and pass the options to the class constructor ie html_processed = true etc
// might need to limit the number returned so we dont get timeouts etc

function pdf2p2_get_gb_processed_post_ids(): array {
    $args = [
        'post_type'      => 'pdf2p2_gutenberg',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'gb_processed',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
    ];
    return get_posts( $args );
}

function pdf2p2_get_gb_unprocessed_post_ids(): array {
    $args = [
        'post_type'      => 'pdf2p2_import',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'html_processed',
                'value'   => '1',
                'compare' => '=',
            ],
            [
                'relation' => 'OR',
                [
                    'key'     => 'gb_processed',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => 'gb_processed',
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ],
        ],
    ];
    return get_posts( $args );
}


function pdf2p2_wrap_block( $name, $content, $attrs = [] ) {
    $attr = $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) : '';
    return "<!-- wp:$name$attr -->\n$content\n<!-- /wp:$name -->\n\n";
}


// fixes the way images are handled in the HTML to Gutenberg conversion
function pdf2p2_build_image_block_from_img_node( DOMElement $img ) : string {
    $src = (string) $img->getAttribute('src');
    if ($src === '') { return ''; }

    $alt = (string) $img->getAttribute('alt');

    $attachment_id = attachment_url_to_postid( $src );

    $attrs = [
        'sizeSlug'        => 'large',
        'linkDestination' => 'none',
    ];
    if ( $attachment_id ) {
        $attrs['id'] = $attachment_id;
    }

    $figure = '<figure class="wp-block-image size-large">'
            . '<img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '"'
            . ( $attachment_id ? ' class="wp-image-' . intval($attachment_id) . '"' : '' )
            . ' />'
            . '</figure>';

    return pdf2p2_wrap_block( 'image', $figure, $attrs );
}


function pdf2p2_node_inner_html( DOMDocument $dom, DOMNode $node ) {
    $html = '';
    foreach ( $node->childNodes as $child ) {
        $html .= $dom->saveHTML( $child );
    }
    return $html;
}

function pdf2p2_html_to_blocks( $html ) {
    libxml_use_internal_errors( true );

    $dom = new DOMDocument();
    $dom->loadHTML( '<!DOCTYPE html><meta charset="utf-8"><div id="__w__">'.$html.'</div>',
  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
 );
    $root = $dom->getElementById( '__w__' );

    $out = '';
    foreach ( $root->childNodes as $node ) {
        $out .= pdf2p2_node_to_block( $dom, $node );
    }
    return trim( $out );
}

function pdf2p2_node_to_block( DOMDocument $dom, DOMNode $node ) {
    if ( $node->nodeType === XML_TEXT_NODE ) {
        $text = trim( $node->nodeValue );
        if ( $text === '' ) {
            return '';
        }
        $p = '<p>' . esc_html( $text ) . '</p>';
        return pdf2p2_wrap_block( 'paragraph', $p );
    }

    if ( $node->nodeType !== XML_ELEMENT_NODE ) {
        return '';
    }

    $name = strtolower( $node->nodeName );
    $html = $dom->saveHTML( $node );

    switch ( $name ) {
        // this fixes md images wrapped in <p> tags
        case 'p': {
            $is_image_only = true;
            $img_node = null;
            $allow = ['img','br','a'];
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    if (trim($child->nodeValue) !== '') { $is_image_only = false; break; }
                    continue;
                }
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $n = strtolower($child->nodeName);
                    if (!in_array($n, $allow, true)) { $is_image_only = false; break; }

                    if ($n === 'img') {
                        $img_node = $child;
                    } elseif ($n === 'a') {
                        // If it's a link, make sure it only wraps an <img>
                        $imgs = $child->getElementsByTagName('img');
                        if ($imgs->length === 1) {
                            $img_node = $imgs->item(0);
                        } else {
                            $is_image_only = false; break;
                        }
                    }
                }
            }
            if ($is_image_only && $img_node instanceof DOMElement) {
                return pdf2p2_build_image_block_from_img_node($img_node);
            }
            $html = $dom->saveHTML($node);
            return pdf2p2_wrap_block( 'paragraph', $html );
        }

        case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
            $level = (int) substr( $name, 1 );
            $inner = pdf2p2_node_inner_html( $dom, $node );
            return pdf2p2_wrap_block( 'heading', "<$name>$inner</$name>", [ 'level' => $level ] );

        case 'ul':
            return pdf2p2_wrap_block( 'list', $html );

        case 'ol':
            return pdf2p2_wrap_block( 'list', $html, [ 'ordered' => true ] );

        case 'pre':
            $inner = pdf2p2_node_inner_html( $dom, $node ); 
            $content = '<pre class="wp-block-code">' . $inner . '</pre>';
            return pdf2p2_wrap_block( 'code', $content );

        case 'blockquote':
            return pdf2p2_wrap_block( 'quote', $html );

        case 'hr':
            return pdf2p2_wrap_block( 'separator', '<hr />' );

        case 'figure':
            $imgs = $node->getElementsByTagName( 'img' );
            if ( $imgs->length > 0 ) {
                $img = $imgs->item(0);
                $src = $img->getAttribute('src');
                $alt = $img->getAttribute('alt');
                $cap = '';
                $caps = $node->getElementsByTagName('figcaption');
                if ( $caps->length > 0 ) {
                    $cap = pdf2p2_node_inner_html( $dom, $caps->item(0) );
                }
                $figure = '<figure class="wp-block-image"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" />'
                        . ( $cap ? '<figcaption>' . $cap . '</figcaption>' : '' )
                        . '</figure>';
                return pdf2p2_wrap_block( 'image', $figure );
            }
            return pdf2p2_wrap_block( 'html', $html );


        case 'img':
            return pdf2p2_build_image_block_from_img_node($node);  

        case 'table':
            $figure = '<figure class="wp-block-table">' . $html . '</figure>';
            return pdf2p2_wrap_block( 'table', $figure );

        default:
            return pdf2p2_wrap_block( 'html', $html );
    }
}

function pdf2p2_process_html_to_gb( int $post_id ): bool {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'pdf2p2_import' ) {
        return false;
    }

    $blocks_content = pdf2p2_html_to_blocks( (string) $post->post_content );
// remove    $blocks_content = pdf2p2_remove_placeholder_image_blocks( $blocks_content );

    $updated = wp_update_post( [
        'ID'           => $post_id,
        'post_type'    => 'pdf2p2_gutenberg',
        'post_status'  => 'draft',
        'post_content' => $blocks_content,
    ], true );

    if ( is_wp_error( $updated ) ) {
        pdf2p2_log( sprintf( 'gutenberg-processing.php — Error updating post %d: %s', $post_id, $updated->get_error_message() ), 'ERROR' );
        return false;
    }

    update_post_meta( $post_id, 'gb_processed', '1' );
    pdf2p2_log( sprintf( 'gutenberg-processing.php — all good. ID: %s' , $post_id) , 'SUCCESS' );
    return true;
}


function pdf2p2_render_gb_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
    return;
    }

    echo '<div class="wrap">';
    echo '<h1>Convert HTML content to Gutenberg</h1>';
    echo '<p>This page lists posts whose HTML is processed and lets you bulk process them to Gutenberg.</p>';

    echo '<h2>Posts Not Converted To GB</h2>';
    $unprocessed = pdf2p2_get_gb_unprocessed_post_ids();
    if ( empty( $unprocessed ) ) {
        echo '<p>No HTML-processed imports waiting for GB conversion.</p>';
    } else {
        echo '<ul>';
        foreach ( $unprocessed as $post_id ) {
            echo '<li>' . esc_html( get_the_title( $post_id ) ) . ' (ID: ' . intval( $post_id ) . ')</li>';
        }
        echo '</ul>';
    }

    echo '<h2>Posts Converted to Gutenberg</h2>';
    $processed = pdf2p2_get_gb_processed_post_ids();
    if ( empty( $processed ) ) {
        echo '<p>No processed posts found.</p>';
    } else {
        echo '<ul>';
        foreach ( $processed as $post_id ) {
            echo '<li>' . esc_html( get_the_title( $post_id ) ) . ' (ID: ' . intval( $post_id ) . ')</li>';
        }
        echo '</ul>';
    }

    echo '<hr />';
    echo '<h2>Bulk Convert to Gutenberg</h2>';
    echo '<p>Enter one or more post IDs (CSV), e.g. <code>12,34,56</code>.</p>';
    echo '<form method="post">';
    wp_nonce_field( 'pdf2p2_process_gb_bulk', 'pdf2p2_process_gb_bulk_nonce' );
    echo '<input type="text" name="gb_process_post_ids" style="width:300px;" '
       . 'placeholder="e.g. 12,34,56" '
       . 'value="' . ( isset( $_POST['gb_process_post_ids'] ) ? esc_attr( $_POST['gb_process_post_ids'] ) : '' ) . '"> ';
    submit_button( __( 'Convert to Gutenberg', 'pdf2p2' ), 'primary', 'gb_process', false );
    echo '</form>';

    if ( ! empty( $_POST['gb_process'] )
      && isset( $_POST['pdf2p2_process_gb_bulk_nonce'] )
      && check_admin_referer( 'pdf2p2_process_gb_bulk', 'pdf2p2_process_gb_bulk_nonce' )
    ) {
        $raw = isset( $_POST['gb_process_post_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['gb_process_post_ids'] ) ) : '';
        $ids = array_filter( array_map( 'intval', preg_split( '/\s*,\s*/', $raw ) ) );

        pdf2p2_log( sprintf( 'gutenberg-processing.php — bulk submit. IDs: %s', implode( ', ', $ids ) ), 'INFO' );

        if ( $ids ) {
            echo '<h3>' . esc_html__( 'Bulk Convert Results', 'pdf2p2' ) . '</h3><ul>';
            foreach ( $ids as $post_id ) {
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    echo '<li>' . sprintf( esc_html__( 'ID %d: permission denied.', 'pdf2p2' ), $post_id ) . '</li>';
                    continue;
                }
                $post = get_post( $post_id );
                if ( ! $post || $post->post_type !== 'pdf2p2_import' ) {
                    echo '<li>' . sprintf( esc_html__( 'ID %d: not a pdf2p2_import post.', 'pdf2p2' ), $post_id ) . '</li>';
                    continue;
                }
                if ( '1' !== get_post_meta( $post_id, 'html_processed', true ) ) {
                    echo '<li>' . sprintf( esc_html__( 'ID %d: HTML not marked as processed.', 'pdf2p2' ), $post_id ) . '</li>';
                    continue;
                }

                $ok = pdf2p2_process_html_to_gb( $post_id );
                if ( $ok ) {
                    echo '<li>' . sprintf(
                        esc_html__( '%1$s (ID %2$d) processed.', 'pdf2p2' ),
                        esc_html( get_the_title( $post_id ) ),
                        $post_id
                    ) . '</li>';
                } else {
                    echo '<li>' . sprintf( esc_html__( 'ID %d: conversion failed.', 'pdf2p2' ), $post_id ) . '</li>';
                }
            }
            echo '</ul>';
        } else {
            echo '<p><em>' . esc_html__( 'No valid post IDs provided.', 'pdf2p2' ) . '</em></p>';
        }
    }
    echo '</div>'; 
}

