<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

global $wp_query;
?>
<main id="primary" class="site-main">

    <header>
        <h1>
            <?php echo esc_html( post_type_archive_title( '', false ) ?: 'Documents' ); ?>
        </h1>
        <p><?php esc_html_e( 'Latest items (100 per page).', 'pdf2p2' ); ?></p>
    </header>

    <?php if ( have_posts() ) : ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Title', 'pdf2p2' ); ?></th>
                    <th><?php esc_html_e( 'JSON', 'pdf2p2' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'pdf2p2' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'pdf2p2' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php while ( have_posts() ) : the_post();
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
                        <td><?php echo esc_html( get_the_date() ); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php
        echo paginate_links( [
            'total'     => (int) $wp_query->max_num_pages,
            'current'   => max( 1, (int) get_query_var( 'paged' ) ),
            'prev_text' => '« ' . esc_html__( 'Previous', 'pdf2p2' ),
            'next_text' => esc_html__( 'Next', 'pdf2p2' ) . ' »',
        ] );
        ?>

    <?php else : ?>
        <p><?php esc_html_e( 'No items found.', 'pdf2p2' ); ?></p>
    <?php endif; ?>

</main>
<?php get_footer(); ?>
