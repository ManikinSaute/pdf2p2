<?php

add_action( 'admin_init', 'pdf2p2_register_settings' );
function pdf2p2_register_settings() {
    add_settings_section(
      'pdf2p2_main_section',
      'OCR & Ingestion Settings',
      '__return_false',
      'pdf2p2-settings'
    );
	register_setting(
	  'pdf2p2_settings_group',
	  'pdf2p2_debug_mode',
	  [
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 0,
	  ]
	);
    register_setting(
        'pdf2p2_settings_group',
        'pdf2p2_total_docs',
        [ 
          'sanitize_callback' => 'absint' 
        ]
    );
    register_setting(
        'pdf2p2_settings_group',
        'pdf2p2_cron_schedule',
        [
          'sanitize_callback' => 'sanitize_text_field',
          'default'           => 'daily', 
        ]
    );
    register_setting(
        'pdf2p2_settings_group',
        'pdf2p2_import_rssfeed_url',
        [ 
          'sanitize_callback' => 'esc_url_raw',
          'default'           => 'https://www.amnesty.org/en/latest/feed/',
        ]
    );
    register_setting(
        'pdf2p2_settings_group',
        'pdf2p2_api_key',
        [
          'type'              => 'string',
          'sanitize_callback' => 'pdf2p2_sanitize_api_key',
          'default'           => '',
        ]
    );
	add_settings_field(
        'pdf2p2_import_rssfeed_url',
        'RSS feed URL',
        'pdf2p2_import_rssfeed_url_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
	add_settings_field(
        'pdf2p2_api_key',
        'OCR API Key',
        'pdf2p2_api_key_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
    add_settings_field(
        'pdf2p2_total_docs',
        'Total Documents to Ingest',
        'pdf2p2_total_docs_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
    add_settings_field(
        'pdf2p2_cron_schedule',
        'Cron Schedule',
        'pdf2p2_cron_schedule_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
    add_settings_field(
        'pdf2p2_debug_mode',
        'De-Bug  Mode',
        'pdf2p2_debug_mode_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
}

function pdf2p2_api_key_field_cb() {
    $key = get_option( 'pdf2p2_api_key', '' );
    printf(
        '<input type="password"
                name="pdf2p2_api_key"
                value=""
                placeholder="%s"
                class="regular-text" />',
        esc_attr( $key ? '••••••••' : '' )
    );

    if ( $key ) {
        echo '<p class="description">An API key is already saved, the length is not indicated by the obsficated value shown.</p>';
    } else {
        echo '<p class="description">Enter your OCR service API key.</p>';
    }
}

function pdf2p2_import_rssfeed_url_field_cb() {
    $pdf2p2_import_rssfeed_url = get_option( 'pdf2p2_import_rssfeed_url', '' );
    printf(
        '<input type="url" id="pdf2p2_import_rssfeed_url" name="pdf2p2_import_rssfeed_url" value="%s" class="regular-text" />'
        . '<p class="description">Enter your import RSS feed here. <br /> Leaving this blank can cause issues, these are example feeds https://www.amnesty.org/en/latest/feed/ or https://feeds.bbci.co.uk/news/rss.xml .</p>',
        esc_attr( $pdf2p2_import_rssfeed_url )
    );
}

function pdf2p2_total_docs_field_cb() {
    $total = get_option( 'pdf2p2_total_docs', 0 );
    printf(
        '<input type="number" min="0" name="pdf2p2_total_docs" value="%s" class="regular-text" />',
        esc_attr( $total )
    );
    echo '<p class="description">This could be used if you had a target number of documents, current not used anywhere.</p>';

}

function pdf2p2_cron_schedule_field_cb() {
    $schedules = wp_get_schedules();
    $current  = get_option( 'pdf2p2_cron_schedule', 'daily' );
    echo '<select name="pdf2p2_cron_schedule">';
    foreach ( $schedules as $key => $sched ) {
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr( $key ),
            selected( $current, $key, false ),
            esc_html( $sched['display'] )
        );
    }
    echo '</select>';
    echo '<p class="description">Select how often to run the ingestion job. This can be shortened by adding a new cron with a plugin such as WP Crontrol by John Blackbourn.</p>';
}

function pdf2p2_debug_mode_cb() {
    $enabled = (int) get_option( 'pdf2p2_debug_mode', 0 );
    printf(
        '<label for="pdf2p2_debug_mode">
            <input type="checkbox" id="pdf2p2_debug_mode" name="pdf2p2_debug_mode" value="1" %s />
            %s
        </label>
        <p class="description">%s</p>',
        checked( 1, $enabled, false ),              
        esc_html__( 'Enable debug mode', 'pdf2p2' ), 
        esc_html__( 'Toggle pdf2p2 debug logging on or off. Turning this on will show some admin notices on this page', 'pdf2p2' ) 
    );
}

function pdf2p2_sanitize_api_key( $input ) {
    $old = get_option( 'pdf2p2_api_key', '' );
    if ( empty( $input ) && $old ) {
        return $old;
    }
        return sanitize_text_field( $input );
}

function pdf2p2_log_setting_change( $option_name, $old_value, $new_value ) {
    if ( strpos( $option_name, 'pdf2p2_' ) !== 0 ) {
        return;
    }
    if ( $old_value === $new_value ) {
        return;
    }
    pdf2p2_log(
        sprintf( 'SETTING Changed %s', $option_name ),
        'INFO'
    );
}
add_action( 'updated_option', 'pdf2p2_log_setting_change', 10, 3 );

function pdf2p2_render_settings_page() {
    // Detect & log when the form was just saved
    if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
        pdf2p2_log( 'SETTING Settings page saved', 'INFO' );
    }

    // The actual form
    ?>
    <div class="wrap">
      <h1>Edit Settings</h1>
      <form method="post" action="options.php">
        <?php
          settings_fields(   'pdf2p2_settings_group' );
          do_settings_sections( 'pdf2p2-settings' );
          submit_button();
        ?>
      </form>
    </div>
    <?php
}