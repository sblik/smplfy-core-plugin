<?php
/**
 * Smplfy Core
 *
 * @package SMP Core
 * @author  Simplify Small Biz
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name: Smplfy Core
 * Version: 1.0.0
 * Description: Core logic for a unified development approach across multiple plugins.
 * Author: Simplify Small Biz
 * Author URI: https://simplifybiz.com
 * Requires PHP: 7.4
 */

namespace SmplfyCore;

define( 'SMP_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once SMP_CORE_PLUGIN_DIR . 'includes/utilities/smplfy-security.php';
require_once SMP_CORE_PLUGIN_DIR . 'includes/utilities/SMPLFY_Require.php';
$require = new SMPLFY_Require( SMP_CORE_PLUGIN_DIR );

try {
	$require->directory( 'includes/hooks' );
	$require->directory( 'includes/entities' );
	$require->directory( 'includes/gravity-forms' );
	$require->directory( 'includes/repositories' );
	$require->directory( 'includes/utilities' );
	$require->directory( 'includes/settings' );
	$require->directory( 'includes/logger' );
} catch ( Exception $e ) {
	error_log( $e->getMessage() );
}


register_activation_hook( __FILE__, 'smp_core_handle_plugin_activation' );

