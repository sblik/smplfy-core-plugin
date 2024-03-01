<?php
/**
 * SMP Core
 *
 * @package SMP Core
 * @author  Simplify Small Biz
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name: SMP Core
 * Version: 1.0.0
 * Description: Core logic for a unified development approach across multiple plugins.
 * Author: Simplify Small Biz
 * Author URI: https://simplifybiz.com
 * Requires PHP: 7.4
 */

define( 'SMP_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once SMP_CORE_PLUGIN_DIR . 'includes/smp-core-handle-plugin-activation.php';
require_once SMP_CORE_PLUGIN_DIR . 'includes/repositories/GravityFormsApiWrapper.php';
require_once SMP_CORE_PLUGIN_DIR . 'includes/entities/SMP_BaseEntity.php';
require_once SMP_CORE_PLUGIN_DIR . 'includes/repositories/SMP_BaseRepository.php';


register_activation_hook( __FILE__, 'smp_core_handle_plugin_activation' );