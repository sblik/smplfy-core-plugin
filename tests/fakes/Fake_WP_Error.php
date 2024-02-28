<?php

class WP_Error {
	private string $errorCode;
	private string $errorMessage;

	public function __construct( $errorCode, $errorMessage ) {
		$this->errorCode    = $errorCode;
		$this->errorMessage = $errorMessage;
	}

	public function get_error_code(): string {
		return $this->errorCode;
	}

	public function get_error_message(): string {
		return $this->errorMessage;
	}
}

function do_action( $hook_name, ...$arg ) {

}

function is_wp_error( $thing ): bool {
	return $thing instanceof WP_Error;
}