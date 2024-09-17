<?php
namespace SmplfyCore;
// Prevent script execution from outside WordPress
if (
	! function_exists( 'get_option' ) ||
	! function_exists( 'add_action' ) ||
	! defined( 'ABSPATH' )
) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}
