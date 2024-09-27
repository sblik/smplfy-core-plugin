<?php
namespace SmplfyCore;
add_action( 'admin_init', 'SmplfyCore\smplfy_settings_init' );

/**
 * custom option and settings
 */
function smplfy_settings_init(): void {
	register_setting( 'smplfy', 'smplfy_options' );

	add_settings_section(
		'smplfy_section_developers',
		'SMPLFY Core Settings',
		'SmplfyCore\smplfy_section_developers_callback',
		'smplfy'
	);

	smplfy_add_settings_field( 'smplfy_field_send_logs_to_data_dog', 'Send Logs to DataDog:' );
	smplfy_add_settings_field( 'smplfy_field_api_url', 'DataDog API Url:' );
	smplfy_add_settings_field( 'smplfy_field_api_key', 'DataDog API Key:' );
}

function smplfy_add_settings_field( string $id, string $title ): void {
    $callback = "SmplfyCore\\{$id}_cb";
	add_settings_field(
		$id,
		$title,
		$callback,
		'smplfy',
		'smplfy_section_developers',
		array( 'label_for' => $id, )
	);
}

/**
 * @param  array  $args  The settings array, defining title, id, callback.
 */
function smplfy_section_developers_callback( array $args ): void {
	?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"></p>
	<?php
}

function smplfy_field_send_logs_to_data_dog_cb( $args ): void {
	smplfy_add_setting_field( $args, 'smplfy_field_send_logs_to_data_dog', 'checkbox' );
}

function smplfy_field_api_url_cb( $args ): void {
	smplfy_add_setting_field( $args, 'smplfy_field_api_url', 'text' );
}

function smplfy_field_api_key_cb( $args ): void {
	smplfy_add_setting_field( $args, 'smplfy_field_api_key', 'password' );
}

/**
 * @param  array  $args
 * @param  string  $setting_id
 * @param  string  $type
 */
function smplfy_add_setting_field( array $args, string $setting_id, string $type ): void {
	$value = get_smplfy_setting_value( $setting_id );

	if ( $type == 'checkbox' ) {
		smplfy_checkbox_field( $args, $value, $type );

		return;
	}

	smplfy_field( $args, $value, $type );
}

/**
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param  array  $args
 * @param $value
 * @param $type
 */
function smplfy_checkbox_field( array $args, $value, $type ) {
	$value = checked( "on", $value, false );

	?>
    <input type="<?php echo esc_attr( $type ) ?>"
           id="<?php echo esc_attr( $args['label_for'] ); ?>"
           name="smplfy_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		<?php echo $value ?>
    >
	<?php
}

function smplfy_field( $args, $value, $type ) {
	?>
    <input type="<?php echo esc_attr( $type ) ?>"
           id="<?php echo esc_attr( $args['label_for'] ); ?>"
           name="smplfy_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
           value="<?php echo $value ?>"
    >
	<?php
}

function get_smplfy_setting_value( $setting_id ): ?string {
	$options = get_option( 'smplfy_options' );

	return esc_attr( $options[ $setting_id ] );
}

function get_smplfy_settings(): SMPLFY_SettingsModel {
	$settings  = get_option( 'smplfy_options' );
	$send_logs = esc_attr( $settings['smplfy_field_send_logs_to_data_dog'] );
	$api_key   = esc_attr( $settings['smplfy_field_api_key'] );
	$api_url   = esc_attr( $settings['smplfy_field_api_url'] );

	return new SMPLFY_SettingsModel( $api_key, $api_url, $send_logs );
}