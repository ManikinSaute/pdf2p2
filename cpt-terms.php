<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pdf2p2_register_cpts() {
    $cpts = [
        'pdf2p2_import'    => [ 'singular' => 'pdf2p2 Import',  'plural' => 'pdf2p2 Imports' ],
        'pdf2p2_gutenberg' => [ 'singular' => 'pdf2p2 Post', 'plural' => 'pdf2p2 Posts' ],
    ];

    foreach ( $cpts as $slug => $labels ) {
        $args = [
            'labels'        => [
                'name'          => $labels['plural'],
                'singular_name' => $labels['singular'],
            ],
            'public'        => true, // ( $slug === 'pdf2p2_gutenberg' ),
            'show_in_menu'  => true, // ( $slug === 'pdf2p2_gutenberg' ),
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_admin_bar'  => true,
            'show_in_nav_menus'  => true,
            'publicly_queryable' => true,
            'exclude_from_search'=> false,
            'menu_position' => 20,
            'supports'      => [ 'title', 'editor', 'custom-fields' ],
            'show_in_rest'  => true, // ( $slug === 'pdf2p2_gutenberg' ),
            'menu_icon'     => 'dashicons-media-document',
            'taxonomies'    => [ 'status' ],
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
        ];

        register_post_type( $slug, $args );
    }
}
add_action( 'init', 'pdf2p2_register_cpts' );

function pdf2p2_register_status_taxonomy() {
    $tax    = 'status';
    $cpts   = [ 'pdf2p2_import', 'pdf2p2_gutenberg' ];
    $labels = [
        'name'          => 'Statuses',
        'singular_name' => 'Status',
        'menu_name'     => 'Status',
        'all_items'     => 'All Statuses',
        'add_new_item'  => 'Add New Status',
        'edit_item'     => 'Edit Status',
        'capability_type'    => 'post',
        'show_in_rest'       => true,
        'supports'=> [ 'title', 'editor', 'revisions'],
    ];

    register_taxonomy( $tax, $cpts, [
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'hierarchical'      => true,
        'rewrite'           => false,
        'show_in_rest'      => true,
        'rest_base'         => 'pdf2p2-status',
    ] );

    $terms = [
        'un_verified'    => 'Un Verified',
        'human_verified' => 'Human Verified',
        'staff_verified' => 'Staff Verified',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $tax ) ) {
            wp_insert_term( $name, $tax, [ 'slug' => $slug ] );
        }
    }
}
add_action( 'init', 'pdf2p2_register_status_taxonomy', 11 );


add_action( 'admin_init', function() {
    $post_types = [ 'pdf2p2_import', 'pdf2p2_gutenberg' ];
    foreach ( $post_types as $pt ) {
        // — existing status column —
        add_filter( "manage_{$pt}_posts_columns", function( $cols ) {
            $cols['mistral_processed'] = __( 'OCR Processed', 'pdf2p2' );
            $cols['html_processed']    = __( 'HTML Processed', 'pdf2p2' );
            $cols['gb_processed']      = __( 'GB Processed', 'pdf2p2' );
            return $cols;
        } );
        add_action( "manage_{$pt}_posts_custom_column", function( $column, $post_id ) {
          if ( in_array( $column, [ 'mistral_processed', 'html_processed', 'gb_processed' ], true ) ) {
                $done = get_post_meta( $post_id, $column, true );
                echo $done
                    ? '<span style="color:green;">✓</span>'
                    : '<span style="color:red;">✕</span>';
            }
        }, 10, 2 );
    }
} );

function pdf2p2_cleanup_status_terms() {
    $taxonomy = 'status';

    // Define the slugs we actually want to keep:
    $keep = [
        'un_verified',
        'human_verified',
        'staff_verified',
    ];

    // Fetch all terms, even if empty:
    $terms = get_terms( [
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ] );

    if ( is_wp_error( $terms ) ) {
        return;
    }

    foreach ( $terms as $term ) {
        if ( ! in_array( $term->slug, $keep, true ) ) {
            // Delete anything not in our “keep” list
            wp_delete_term( $term->term_id, $taxonomy );
        }
    }
}
add_action( 'init', 'pdf2p2_cleanup_status_terms', 12 );