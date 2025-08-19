<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function sideload_embedded_images( string $html, int $post_id ): string {
    $html = (string) $html;
    if ($html === '' || stripos($html, '<img') === false) {
        return $html;
    }

    if ( ! function_exists('media_handle_sideload') ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    @set_time_limit(120);
    if ( function_exists('wp_raise_memory_limit') ) {
        wp_raise_memory_limit('image');
    }

    libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html,
  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
);
    libxml_clear_errors();

    $imgs = $doc->getElementsByTagName('img');
    $MAX_PER_PASS = 10;
    $processed_count = 0;

    $replacements = [];
    foreach ($imgs as $img) {
        if ($processed_count >= $MAX_PER_PASS) { break; }

        $src = (string) $img->getAttribute('src');
        if ($src === '') { continue; }

        if (preg_match('#^image/(png|jpe?g|gif|webp);base64,#i', $src)) {
            $src = 'data:' . $src;
        }

        if (!preg_match('#^data:image/([a-zA-Z0-9.+-]+);base64,#', $src)) {
            continue; 
        }

        $t0 = microtime(true);
        $new_url = sideload_single_data_image_logged($src, $post_id);
        $elapsed = round((microtime(true) - $t0) * 1000);

        if ($new_url) {
            $replacements[] = [$img, $new_url];
            pdf2p2_log(sprintf('sideload: OK in %dms → %s', $elapsed, $new_url), 'INFO');
            $processed_count++;
        } else {
            pdf2p2_log(sprintf('sideload: FAIL in %dms (src len=%d)', $elapsed, strlen($src)), 'ERROR');
        }
    }

    foreach ($replacements as [$img, $new_url]) {
        $img->setAttribute('src', $new_url);
        if (! $img->hasAttribute('loading')) {
            $img->setAttribute('loading', 'lazy');
        }
    }

    pdf2p2_log(sprintf('sideload_embedded_images: replaced %d image(s) (max %d per pass)', $processed_count, $MAX_PER_PASS), 'INFO');
    return $doc->saveHTML();
}

function sideload_single_data_image_logged( string $data_uri, int $post_id ) {
    if (! preg_match('#^data:image/([a-zA-Z0-9.+-]+);base64,(.*)$#s', $data_uri, $m)) {
        pdf2p2_log('sideload: not a valid data URI', 'ERROR');
        return false;
    }
    $mime = strtolower($m[1]);
    $payload = $m[2];

    if (strlen($payload) > 10 * 1024 * 1024) {
        pdf2p2_log(sprintf('sideload: payload too large (%d bytes base64)', strlen($payload)), 'WARNING');
        return false;
    }

    $ext = ($mime === 'jpeg') ? 'jpg' : $mime;
    if (! in_array($ext, ['png','jpg','jpeg','gif','webp'], true)) {
        pdf2p2_log('sideload: unsupported mime ' . $mime, 'WARNING');
        return false;
    }

    $bin = base64_decode($payload);
    if ($bin === false) {
        pdf2p2_log('sideload: base64_decode failed', 'ERROR');
        return false;
    }

    $tmp = wp_tempnam('embedded-image');
    if (! $tmp) {
        pdf2p2_log('sideload: wp_tempnam failed', 'ERROR');
        return false;
    }
    if (file_put_contents($tmp, $bin) === false) {
        wp_delete_file($tmp);
        pdf2p2_log('sideload: write tmp failed', 'ERROR');
        return false;
    }

    add_filter('big_image_size_threshold', '__return_zero', 99);

    $file_array = [
        'name'     => 'embedded-' . wp_generate_password(8, false) . '.' . $ext,
        'tmp_name' => $tmp,
    ];

    $att_id = media_handle_sideload($file_array, $post_id);

    remove_filter('big_image_size_threshold', '__return_zero', 99);

    if (is_wp_error($att_id)) {
        wp_delete_file($tmp);
        pdf2p2_log('sideload: media_handle_sideload error: ' . $att_id->get_error_message(), 'ERROR');
        return false;
    }

    $url = wp_get_attachment_url($att_id);
    if (! $url) {
        pdf2p2_log('sideload: wp_get_attachment_url returned empty', 'ERROR');
        return false;
    }
    return $url;
}