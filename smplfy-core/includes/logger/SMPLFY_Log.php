<?php
namespace SmplfyCore;
class SMPLFY_Log {
	/**
	 * @param  $message
	 * @param  null  $data
	 * @param  bool  $log_to_file
	 *
	 * @return void
	 */
	public static function error( $message, $data = null, bool $log_to_file = true ): void {
		self::log_message( $message, 'error', $data, $log_to_file );
	}

	/**
	 * @param  $message
	 * @param  string  $type
	 * @param  null  $data
	 * @param  bool  $log_to_file
	 *
	 * @return void
	 */
	private static function log_message( $message, string $type, $data = null, bool $log_to_file = true ): void {

		if ( true !== WP_DEBUG ) {
			return;
		}
		$message_to_log = $message;
		$data_to_log    = $data != null ? print_r( $data, true ) : $data;

		if ( is_array( $message ) || is_object( $message ) ) {
			$message_to_log = print_r( $message, true );
		}

		$value_to_log = $data_to_log ? "$message_to_log\r\n$data_to_log" : $message_to_log;

		if ( $log_to_file ) {
			error_log( $value_to_log );
		}
		self::log_to_data_dog( $value_to_log, $type );
	}

	/**
	 * @param  $message
	 * @param  string  $type
	 *
	 * @return void
	 */
	private static function log_to_data_dog( $message, string $type ): void {
		$logger_settings = get_smplfy_settings();

		if ( ! $logger_settings->is_send_to_data_dog() ) {
			return;
		}
		if ( ! $logger_settings->get_api_key() || ! $logger_settings->get_api_url() ) {
			error_log( 'Unable to log messages to data dog. Please provide a "API url" and "API key" in the BS Logger settings page.' );

			return;
		}

		$body = array(
			'ddsource' => site_url(),
			'ddtags'   => '',
			'message'  => $message,
			'service'  => self::get_plugin_name(),
			'level'    => $type,
		);

		wp_remote_post( $logger_settings->get_api_url(), array(
			'body'    => json_encode( $body ),
			'headers' => array(
				'DD-API-KEY'   => $logger_settings->get_api_key(),
				'Content-Type' => 'application/json',
			),
		) );
	}

	public static function get_plugin_name(): string {
		$trace                = debug_backtrace();
		$file_name            = $trace[3]['file'];
		$file_path            = substr( $file_name, strpos( $file_name, 'plugins' ) );
		$normalized_file_path = str_replace( "\\", '/', $file_path );

		return explode( "/", $normalized_file_path )[1];
	}

	/**
	 * @param  $message
	 * @param  null  $data
	 * @param  bool  $log_to_file
	 *
	 * @return void
	 */
	public static function warn( $message, $data = null, bool $log_to_file = true ): void {
		self::log_message( $message, 'warning', $data, $log_to_file );
	}

	/**
	 * @param  $message
	 * @param  null  $data
	 * @param  bool  $log_to_file
	 *
	 * @return void
	 */
	public static function info( $message, $data = null, bool $log_to_file = true ): void {
		self::log_message( $message, 'info', $data, $log_to_file );
	}
}