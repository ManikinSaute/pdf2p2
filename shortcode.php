<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


function pdf2p2_list_gutenberg_posts_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'per_page' => 20,
        'paged'    => max( 1, get_query_var( 'paged', 1 ) ),
    ], $atts, 'pdf2p2_list' );

    $query = new WP_Query( [
        'post_type'      => 'pdf2p2_gutenberg',
        'posts_per_page' => intval( $atts['per_page'] ),
        'paged'          => intval( $atts['paged'] ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    ob_start();

    if ( $query->have_posts() ) : ?>
        <div class="wp-block-table">
            <table style="width:100%;">
                <thead>
                    <tr>
                        <th style="text-align:left;"><?php esc_html_e( 'Title', 'pdf2p2' ); ?></th>
                        <th style="text-align:left;"><?php esc_html_e( 'JSON', 'pdf2p2' ); ?></th>
                        <th style="text-align:left;"><?php esc_html_e( 'Status', 'pdf2p2' ); ?></th>
                        <th style="text-align:left;"><?php esc_html_e( 'Date', 'pdf2p2' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php while ( $query->have_posts() ) : $query->the_post();
                    $post_id  = get_the_ID();
                    $type     = get_post_type( $post_id );
                    $json_url = rest_url( "wp/v2/{$type}/{$post_id}" );
                    $terms    = get_the_terms( $post_id, 'status' );
                    $status   = ( $terms && ! is_wp_error( $terms ) )
                        ? implode( ', ', wp_list_pluck( $terms, 'name' ) )
                        : '—';
                    ?>
                    <tr>
                        <td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
                        <td><a href="<?php echo esc_url( $json_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $json_url ); ?></a></td>
                        <td><?php echo esc_html( $status ); ?></td>
                        <td><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination" style="margin-top:1.5rem;">
            <?php
            echo paginate_links( [
                'total'   => $query->max_num_pages,
                'current' => $atts['paged'],
                'prev_text' => '&laquo; ' . __( 'Previous', 'pdf2p2' ),
                'next_text' => __( 'Next', 'pdf2p2' ) . ' &raquo;',
            ] );
            ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e( 'No items found.', 'pdf2p2' ); ?></p>
    <?php endif;

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'pdf2p2_list', 'pdf2p2_list_gutenberg_posts_shortcode' );
