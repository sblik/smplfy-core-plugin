<?php
/**
 * BS Core
 *
 * @package BS Core
 * @author Simplify Small Biz
 * @since 1.0.0
 *
 * @wordpress-plugin
 * Plugin Name: BS Core
 * Version: 1.0.0
 * Description: Core logic for a unified development approach across multiple plugins.
 * Author: Simplify Small Biz
 * Author URI: https://simplifybiz.com
 * Requires PHP: 7.3
 */

define( 'BS_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once BS_CORE_PLUGIN_DIR . 'includes/bs-core-handle-plugin-activation.php';

register_activation_hook( __FILE__, 'bs_core_handle_plugin_activation' );