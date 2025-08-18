<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function render_pdf2p2_home_page() {
	$settings_url   = admin_url('admin.php?page=pdf2p2-settings');
	$feed_url       = admin_url('admin.php?page=pdf2p2-rss-feed');
	$import_url     = admin_url('admin.php?page=pdf2p2-import');
	$imported_url   = admin_url('edit.php?post_type=pdf2p2_import');
	$md_convert_url = admin_url('admin.php?page=pdf2p2-md-gb');
	$gutenberg_url  = admin_url('edit.php?post_type=pdf2p2_gutenberg');
	$logs_url       = admin_url('admin.php?page=pdf2p2-logs');
	$mistral_url    = admin_url('admin.php?page=pdf2p2-mistral');
	$mistral_send_url = admin_url('admin.php?page=pdf2p2-mistral-send');
	$run_scripts_url = admin_url('admin.php?page=pdf2p2-run-scripts');
	$md_gb_url      = admin_url('admin.php?page=pdf2p2-md-gb');
	$github_url	= 'https://github.com/ManikinSaute/pdf2p2';

	echo '<div class="wrap">';
	echo '<h1>pdf2p&sup2; - Welcome.</h1>';

	echo '<h2>Getting Started.</h2>';
	echo '<p>Read the below to get a feel for how this plugin works.</p>';
	echo '<ul> ';
	echo '<li>We have two Custom Post Types: pdf2p&sup2; Import and pdf2p&sup2; Posts.</li>';
	echo '<li>Import is hidden and is where the processing takes place.</li>';
	echo '<li>We have a Custom Taxonomy with the following terms: Un Verified, Human Verified and Staff Verified.</li>';
	echo '<li>We have some speical post meta for example, Mistral Processed, and it is set to True or False.</li>';
	echo '<li>We have some also save the original file URL which is sent to the OCR service and the hash of that file.</li>';
	echo '</ul>';

	echo '<h2>How To Use This Plugin.</h2>';
	echo '<p>Follow these steps to get started with the pdf2p2 plugin:</p>';
	echo '<ol>';
	echo '<li>Go to the <a href="' . esc_url($settings_url) . '">Settings page</a> and add an RSS feed URL.</li>';
	echo '<li>Then add an API key from Mistral AI OCR service.</li>';
	echo '<li>Then set up a cron job frequency.</li>';
	echo '</ol>';

	echo '<p>Thats it, files should be automaticly processed and end up in your <a href="' . esc_url($gutenberg_url) . '">pdf2p&sup2; custom post type</a>. If you can not wait then you can <a href="' . esc_url($run_scripts_url) . '">run / for the cron jobs here</a>.</p>';



	echo '<h2>Having Issues?</h2>';
	echo '<p>Follow these steps to get started with degugging the pdf2p2 plugin:</p>';

	echo '<ol> ';

	echo '<li>Check the <a href="' . esc_url($feed_url) . '">Feed page</a> to check if there are PDFs that need importing.</li>';
	echo '<li>Go to the <a href="' . esc_url($settings_url) . '">Settings page</a> and add turn on debug mode. View notices, turn off.</li>';
	echo '<li>Check the <a href="' . esc_url($logs_url) . '">logs page</a> for any errors. <a href="' . esc_url($run_scripts_url) . '">Run some scripts </a> and re test, clear logs as needed.</li>';
	echo '<li>Test adding a different PDF on the <a href="' . esc_url($import_url) . '">Import PDF</a> page.</li>';	
	echo '<li>Check the <a href="' . esc_url($imported_url) . '">Imported PDFs Custom Post Type</a> to see if the files have been imported but not fully processed.</li>';
	echo '<li>Check if there are any files <a href="' . esc_url($md_convert_url) . '">MD Convert page</a>.</li>';	
	echo '<li>Check a sinlge file by post ID on the <a href="' . esc_url($mistral_url) . '">Check File Page</a>, to see more information on files.</li>';
	echo '<li>Test sending a sinle PDF by post ID to <a href="' . esc_url($mistral_send_url) . '">Mistral OCR Send Page</a>.</li>';
	// echo '<li>Check the <a href="' . esc_url($md_gb_url) . '">MD Gutenberg page</a> to see if the files have been converted to Gutenberg blocks.</li>';
	echo '<li><a href="' . esc_url($run_scripts_url) . '">Run Scripts page</a> to see if the scripts can be manully run.</li>';
		echo '</ol>';

	echo '<p>TO DO: There are still many things to, including tidying, sanatisation, escaping, caching, number-only-once-ing N3Cing (I refusing to write that) . More info can be seen <a href="' . esc_url($github_url) . '">here</a>.</li>';
	echo '</div>';
}
