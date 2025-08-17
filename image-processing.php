<?php
/**
 * Sideload base64 images found in HTML and rewrite <img src="..."> to attachment URLs.
 *
 * @param string $html      The HTML content to process.
 * @param int    $post_id   The post ID to attach uploads to.
 * @return string Updated HTML with <img> src rewritten to attachment URLs.
 */
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

    libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $imgs = $doc->getElementsByTagName('img');

    $replacements = [];

    foreach ($imgs as $img) {
        $src = $img->getAttribute('src');
        if (! $src) {
            continue;
        }

        if (preg_match('#^image/(png|jpe?g|gif|webp);base64,#i', $src)) {
            $src = 'data:' . $src;
        }

        if (preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#i', $src, $m)) {
            $new_url = _sideload_single_data_image($src, $post_id);
            if ($new_url) {
                $replacements[] = [$img, $new_url];
            }
        }
    }

    // Apply replacements
    foreach ($replacements as [$img, $new_url]) {
        $img->setAttribute('src', $new_url);
        if (! $img->hasAttribute('loading')) {
            $img->setAttribute('loading', 'lazy');
        }
    }

    return $doc->saveHTML();
}

/**
 * Helper: take a data:image/...;base64,... string, write a temp file, and sideload it.
 *
 * @param string $data_uri
 * @param int    $post_id
 * @return string|false Attachment URL on success, false on failure.
 */
function _sideload_single_data_image( string $data_uri, int $post_id ) {
    if (! preg_match('#^data:image/([a-zA-Z0-9.+-]+);base64,(.*)$#s', $data_uri, $m)) {
        return false;
    }
    $mime = strtolower($m[1]);
    $payload = $m[2];

    $ext = $mime === 'jpeg' ? 'jpg' : $mime;
    if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
        return false;
    }

    $bin = base64_decode($payload);
    if ($bin === false) {
        return false;
    }

    $tmp = wp_tempnam('embedded-image');
    if (! $tmp) {
        return false;
    }
    file_put_contents($tmp, $bin);

    $file_array = [
        'name'     => 'embedded-' . wp_generate_password(8, false) . '.' . $ext,
        'tmp_name' => $tmp,
    ];

    $att_id = media_handle_sideload($file_array, $post_id);
    if (is_wp_error($att_id)) {
        @unlink($tmp);
        return false;
    }

    $url = wp_get_attachment_url($att_id);
    return $url ?: false;
}