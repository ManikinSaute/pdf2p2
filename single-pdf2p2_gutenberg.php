<?php
// single-pdf2p2_gutenberg_custom.php
// Custom template with sidebar and Gutenberg-style block classes

get_header();
?>

<main class="wp-block-group is-layout-flow wp-block-group-is-layout-flow" id="wp--skip-link--target">

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="wp-block-group container container--feature is-layout-flow wp-block-group-is-layout-flow">
            <figure class="aimc-ignore wp-block-image article-figure is-stretched has-caption">
                <?php the_post_thumbnail( 'full', [ 'class' => 'aiic-ignore wp-image-' . get_post_thumbnail_id() ] ); ?>
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
                            <div class="wp-block-button is-style-light">
                                <a href="<?php echo esc_url( get_post_type_archive_link( 'pdf2p2_import' ) ); ?>" class="wp-block-button__link wp-element-button">
                                    <span class="icon-arrow-left"></span>
                                    <span><?php esc_html_e( 'Back to PDFs', 'text-domain' ); ?></span>
                                </a>
                            </div>
                        </div>
                        <?php // Add any share buttons here if desired ?>
                    </div>

                    <div class="wp-block-group article-metaData is-layout-flow wp-block-group-is-layout-flow">
                        <div class="publishedDate wp-block-post-date">
                            <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
                                <?php echo esc_html( get_the_date() ); ?>
                            </time>
                        </div>
                        <?php // you can insert taxonomy/status here ?>
                    </div>

                    <h1 class="article-title wp-block-post-title"> </h1>

                </header>

                <article class="wp-block-group article-content is-layout-flow wp-block-group-is-layout-flow" itemprop="articleBody">
                    <?php the_content(); ?>
                </article>

                <footer class="wp-block-group article-footer is-layout-flow wp-block-group-is-layout-flow">


                </footer>

            </section>

            <aside class="wp-block-group article-sidebar">
                <?php if ( is_active_sidebar( 'pdf2p2-sidebar' ) ) : ?>
                    <?php dynamic_sidebar( 'pdf2p2-sidebar' ); ?>
                <?php else : ?>

                      <div class="widget widget-json-endpoint" style="margin-top:2rem;">
    <h2 class="widget-title">Doc Meta data</h2>

                                        <?php 
                    // Original PDF link
                    if ( $orig = get_post_meta( get_the_ID(), 'pdf2p2_original_file_path', true ) ) : ?>
                        <p><strong><?php esc_html_e( 'Original PDF:', 'text-domain' ); ?></strong> 
                        <a href="<?php echo esc_url( $orig ); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html( basename( $orig ) ); ?>
                        </a></p>
                    <?php endif;

                    // Stored File link
                    if ( $new_url = get_post_meta( get_the_ID(), 'pdf2p2_new_file_url', true ) ) : ?>
                        <p><strong><?php esc_html_e( 'Stored File:', 'text-domain' ); ?></strong> 
                        <a href="<?php echo esc_url( $new_url ); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html( basename( $new_url ) ); ?>
                        </a></p>
                    <?php endif;

                    // OCR Processed flag
                    ?>
                    <p><strong><?php esc_html_e( 'OCR Processed:', 'text-domain' ); ?></strong> 
                        <?php echo get_post_meta( get_the_ID(), 'mistral_processed', true ) ? esc_html__( 'Yes', 'text-domain' ) : esc_html__( 'No', 'text-domain' ); ?>
                    </p>
                    <?php
                    // Status taxonomy
                    if ( $terms = get_the_terms( get_the_ID(), 'status' ) ) : ?>
                        <p><strong><?php esc_html_e( 'Status:', 'text-domain' ); ?></strong> 
                        <?php echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) ); ?></p>
                    <?php endif; ?>
                  <?php   if ( is_singular( 'pdf2p2_gutenberg' ) ) : 
                  
    // Get this post’s type and ID
    $type    = get_post_type();
    $post_id = get_the_ID();

    // Build the WP-REST URL: /wp-json/wp/v2/{post_type}/{ID}
    $json_url = rest_url( "wp/v2/{$type}/{$post_id}" );
?>
  <div class="widget widget-json-endpoint" style="margin-top:2rem;">
    <h2 class="widget-title">JSON Endpoint</h2>
    <a href="<?php echo esc_url( $json_url ); ?>" target="_blank">
      <?php echo esc_html( $json_url ); ?>
    </a>
  </div>
<?php endif; ?>


                <?php endif; ?>
            </aside>

        </div>
    </div>

</main><!-- #primary -->

<?php
get_footer();
