<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*

function pdf2p2_get_gutenberg_candidates(): array {
    $args = [
        'post_type'      => 'pdf2p2_import',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'mistral_processed',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
    ];
    return get_posts( $args );
}

function pdf2p2_render_md_gb_page() {
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
    echo '<div class="wrap">';
    echo '<h1>Imports Ready To Move</h1>';
    if ( isset( $_POST['convert_post_id'] ) ) {
        $post_id = absint( wp_unslash( $_POST['convert_post_id'] ) );
        pdf2p2_move_post_to_gutenberg( $post_id );
        echo '<div class="notice notice-success"><p>Post moved successfully!</p></div>';
    }

    $to_convert = pdf2p2_get_gutenberg_candidates();
    if ( empty( $to_convert ) ) {
        echo '<p>No processed imports to convert.</p>';
    } else {
        echo '<ul>';
        foreach ( $to_convert as $post_id ) {
            $title = get_the_title( $post_id );
            echo '<li>' . esc_html( $title ) . ' &nbsp;';
            echo '<form method="post" style="display:inline">';
            echo '<input type="hidden" name="convert_post_id" value="' . esc_attr( $post_id ) . '">';
            submit_button( 'Process', 'small', '', false );
            echo '</form>';
            echo '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
}

function pdf2p2_move_post_to_gutenberg( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'pdf2p2_import' ) {
        return;
    }
    if ( ! class_exists( 'Parsedown' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'Parsedown.php';
    }
    $Parsedown     = new Parsedown();
    $html_content  = $Parsedown->text( $post->post_content );
    $blocks_content = pdf2p2_html_to_blocks( $html_content );
    wp_update_post( [
        'ID'           => $post_id,
        'post_type'    => 'pdf2p2_gutenberg',
        'post_status'  => 'draft',
        'post_content' => $blocks_content,
    ] );
    wp_set_object_terms( $post_id, [], 'status', false );
}

function pdf2p2_wrap_block( $name, $content, $attrs = [] ) {
    $attr = $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) : '';
    return "<!-- wp:$name$attr -->\n$content\n<!-- /wp:$name -->\n\n";
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
    $dom->loadHTML( '<!DOCTYPE html><meta charset="utf-8"><div id="__w__">'.$html.'</div>' );
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
        case 'p':
            return pdf2p2_wrap_block( 'paragraph', $html );

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
            $src = $node->getAttribute('src');
            $alt = $node->getAttribute('alt');
            $figure = '<figure class="wp-block-image"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" /></figure>';
            return pdf2p2_wrap_block( 'image', $figure );

        case 'table':
            $figure = '<figure class="wp-block-table">' . $html . '</figure>';
            return pdf2p2_wrap_block( 'table', $figure );

        default:
            return pdf2p2_wrap_block( 'html', $html );
    }
}


 */