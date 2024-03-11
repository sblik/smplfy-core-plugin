<?php

add_action( 'admin_menu', 'bslogger_settings_page' );

/**
 * Add the top level menu page.
 */
function bslogger_settings_page() {
	add_menu_page(
		'BS Logger',
		'BS Logger',
		'manage_options',
		'BS Logger',
		'bslogger_settings_page_html'
	);
}

function bslogger_settings_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error(
			'bslogger_messages',
			'bslogger_message',
			'Settings Saved',
			'updated'
		);
	}

	settings_errors( 'bslogger_messages' );
	?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
			<?php
			settings_fields( 'bslogger' );
			do_settings_sections( 'bslogger' );
			submit_button( 'Save Settings' );
			?>
        </form>
    </div>
	<?php
}