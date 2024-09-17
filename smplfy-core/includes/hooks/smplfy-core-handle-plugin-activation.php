<?php
namespace SmplfyCore;
function smp_core_handle_plugin_activation() {
	$muPluginsDir = WP_CONTENT_DIR . '/mu-plugins';
	$loaderFile   = $muPluginsDir . '/smplfy-core-loader.php';

	/*
	 * The mu-plugins directory is a special directory in WordPress that is always loaded first
	 * Check if the mu-plugins directory exists, if not, create it
	 */
	if ( ! file_exists( $muPluginsDir ) ) {
		mkdir( $muPluginsDir, 0755, true );
	}

	/*
	 * Check if the loader file already exists, if not, create it
	 * The loader file is used to ensure that the SMPLFY Core plugin is loaded before all other plugins
	 */
	if ( ! file_exists( $loaderFile ) ) {
		$fileContent = "<?php\n\n";
		$fileContent .= "/**\n";
		$fileContent .= " * Plugin Name: Smplfy Core Loader\n";
		$fileContent .= " * Description: Ensures that the Smplfy Core plugin is loaded before all other plugins\n";
		$fileContent .= " */\n";
		$fileContent .= "require_once WP_PLUGIN_DIR  . '/smplfy-core/smplfy-core.php';\n";

		file_put_contents( $loaderFile, $fileContent );
	}
}

