<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$post_id        = get_the_ID();
$post_type      = get_post_type( $post_id );
$thumb_id       = get_post_thumbnail_id( $post_id );
$archive_gb     = get_post_type_archive_link( 'pdf2p2_gutenberg' );
$archive_import = get_post_type_archive_link( 'pdf2p2_import' );

$nz = function( $v ) {
    if ( is_string( $v ) ) { $v = trim( $v ); }
    return $v !== '' && $v !== null ? $v : '—';
};

$reading_time = function( $content ) {
    $words = str_word_count( wp_strip_all_tags( $content ) );
    $mins  = max( 1, (int) ceil( $words / 200 ) );
    return [ $words, $mins ];
};

$stored_url   = get_post_meta( $post_id, 'pdf2p2_new_file_url', true );
$attach_id    = (int) get_post_meta( $post_id, 'pdf2p2_attachment_id', true );
$attach_path  = $attach_id ? get_attached_file( $attach_id ) : '';
$attach_size  = ( $attach_path && file_exists( $attach_path ) ) ? size_format( filesize( $attach_path ) ) : '';
$orig_url     = get_post_meta( $post_id, 'pdf2p2_original_file_path', true );
$file_hash    = get_post_meta( $post_id, 'pdf2p2_file_hash', true );

$ocr_done     = get_post_meta( $post_id, 'mistral_processed', true ) ? 'Yes' : 'No';
$html_done    = get_post_meta( $post_id, 'html_processed', true ) ? 'Yes' : 'No';
$gb_done      = get_post_meta( $post_id, 'gb_processed', true ) ? 'Yes' : 'No';

list( $word_count, $mins ) = $reading_time( get_post_field( 'post_content', $post_id ) );

$terms_status = get_the_terms( $post_id, 'status' );
$terms_names  = $terms_status && ! is_wp_error( $terms_status ) ? implode( ', ', wp_list_pluck( $terms_status, 'name' ) ) : '—';

$json_url = rest_url( "wp/v2/{$post_type}/{$post_id}" );

?>

<main class="wp-block-group is-layout-flow wp-block-group-is-layout-flow" id="wp--skip-link--target">

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="wp-block-group container container--feature is-layout-flow wp-block-group-is-layout-flow">
            <figure class="wp-block-image article-figure is-stretched has-caption">
                <?php echo wp_get_attachment_image( $thumb_id, 'full', false, [ 'class' => 'wp-image-' . (int) $thumb_id ] ); ?>
                <?php if ( $caption = get_the_post_thumbnail_caption() ) : ?>
                    <figcaption class="wp-element-caption"><?php echo esc_html( $caption ); ?></figcaption>
                <?php endif; ?>
            </figure>
        </div>
    <?php endif; ?>

    <div class="wp-block-group container has-gutter is-layout-flow wp-block-group-is-layout-flow">
        <div class="wp-block-group article-container is-layout-flow wp-block-group-is-layout-flow">

            <section class="wp-block-group article has-sidebar is-layout-flow wp-block-group-is-layout-flow">

                <header class="wp-block-group article-header is-layout-flow wp-block-group-is-layout-flow">

                    <div class="wp-block-group article-metaActions is-layout-flow wp-block-group-is-layout-flow">
                        <div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">
                            <?php if ( $archive_gb ) : ?>
                            <div class="wp-block-button is-style-light">
                                <a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $archive_gb ); ?>">
                                    <span class="icon-arrow-left" aria-hidden="true"></span>
                                    <span><?php esc_html_e( 'Back to Gutenberg Library', 'pdf2p2' ); ?></span>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="wp-block-group article-metaData is-layout-flow wp-block-group-is-layout-flow">
                        <div class="modifiedDate">
                            <small><?php esc_html_e( 'Updated:', 'pdf2p2' ); ?>
                                <time datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C ) ); ?>">
                                    <?php echo esc_html( get_the_modified_date() ); ?>
                                </time>
                            </small>
                        </div>
                        <div class="reading">
                            <small>
                                <?php
                                /* translators: 1: word count, 2: minutes */
                                echo esc_html( sprintf( _n( '%1$d word • ~%2$d min read', '%1$d words • ~%2$d min read', $word_count, 'pdf2p2' ), $word_count, $mins ) );
                                ?>
                            </small>
                        </div>
                    </div>

                    <h1 class="article-title wp-block-post-title"><?php the_title(); ?></h1>

                </header>

                <article class="wp-block-group article-content is-layout-flow wp-block-group-is-layout-flow" itemprop="articleBody">
                    <?php the_content(); ?>
                </article>

                <footer class="wp-block-group article-footer is-layout-flow wp-block-group-is-layout-flow">
                    <?php
                    the_post_navigation( [
                        'prev_text' => '<span aria-hidden="true">←</span> %title',
                        'next_text' => '%title <span aria-hidden="true">→</span>',
                        'screen_reader_text' => esc_html__( 'Post navigation', 'pdf2p2' ),
                    ] );
                    ?>
                </footer>

            </section>

            <aside class="wp-block-group article-sidebar" aria-labelledby="pdf2p2-sidebar-heading">
                <h2 id="pdf2p2-sidebar-heading" class="screen-reader-text"><?php esc_html_e( 'Document details', 'pdf2p2' ); ?></h2>

                <?php if ( is_active_sidebar( 'pdf2p2-sidebar' ) ) : ?>

                    <?php dynamic_sidebar( 'pdf2p2-sidebar' ); ?>

                <?php else : ?>

                    <div class="widget">
                        <h3 class="widget-title"><?php esc_html_e( 'Document Metadata', 'pdf2p2' ); ?></h3>
                        <ul class="wp-block-list">
                            <li><strong><?php esc_html_e( 'Post ID:', 'pdf2p2' ); ?></strong> <?php echo (int) $post_id; ?></li>
                            <li><strong><?php esc_html_e( 'Status:', 'pdf2p2' ); ?></strong> <?php echo esc_html( $terms_names ); ?></li>
                            <li><strong><?php esc_html_e( 'OCR Processed:', 'pdf2p2' ); ?></strong> <?php echo esc_html( $ocr_done ); ?></li>
                            <li><strong><?php esc_html_e( 'HTML Processed:', 'pdf2p2' ); ?></strong> <?php echo esc_html( $html_done ); ?></li>
                            <li><strong><?php esc_html_e( 'Gutenberg Processed:', 'pdf2p2' ); ?></strong> <?php echo esc_html( $gb_done ); ?></li>
                        </ul>
                    </div>

                    <div class="widget">
                        <h3 class="widget-title"><?php esc_html_e( 'Files', 'pdf2p2' ); ?></h3>
                        <ul class="wp-block-list">
                            <li>
                                <strong><?php esc_html_e( 'Original PDF:', 'pdf2p2' ); ?></strong>
                                <?php if ( $orig_url ) : ?>
                                    <a href="<?php echo esc_url( $orig_url ); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html( wp_basename( wp_parse_url( $orig_url, PHP_URL_PATH ) ) ); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html( $nz('') ); ?>
                                <?php endif; ?>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Stored File:', 'pdf2p2' ); ?></strong>
                                <?php if ( $stored_url ) : ?>
                                    <a href="<?php echo esc_url( $stored_url ); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_attr( wp_basename( wp_parse_url( $stored_url, PHP_URL_PATH ) ) ); ?>
                                    </a>
                                    <?php if ( $attach_size ) : ?>
                                        <small>(<?php echo esc_html( $attach_size ); ?>)</small>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <?php echo esc_html( $nz('') ); ?>
                                <?php endif; ?>
                            </li>
                            <li><strong><?php esc_html_e( 'Attachment ID:', 'pdf2p2' ); ?></strong> <?php echo $attach_id ? (int) $attach_id : '—'; ?></li>
                            <li><strong><?php esc_html_e( 'SHA-256:', 'pdf2p2' ); ?></strong> <code style="word-break:break-all;"><?php echo esc_html( $nz( $file_hash ) ); ?></code></li>
                        </ul>
                    </div>

                    <div class="widget">
                        <h3 class="widget-title"><?php esc_html_e( 'Links', 'pdf2p2' ); ?></h3>
                        <ul class="wp-block-list">
                            <li><strong><?php esc_html_e( 'JSON:', 'pdf2p2' ); ?></strong> <a href="<?php echo esc_url( $json_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $json_url ); ?></a></li>
                            <?php endif; ?>
                            <?php if ( current_user_can( 'edit_post', $post_id ) ) : ?>
                                <li><strong><?php esc_html_e( 'Edit:', 'pdf2p2' ); ?></strong> <a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php esc_html_e( 'Open editor', 'pdf2p2' ); ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
            </aside>

        </div>
    </div>

</main><!-- #primary -->

<?php
get_footer();
