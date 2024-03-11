<?php

function bs_handle_plugin_activated() {
	$muPluginsDir = WP_CONTENT_DIR . '/mu-plugins';
	$loaderFile   = $muPluginsDir . '/bs-logger-loader.php';

	/*
	 * The mu-plugins directory is a special directory in WordPress that is always loaded first
	 * Check if the mu-plugins directory exists, if not, create it
	 */
	if ( ! file_exists( $muPluginsDir ) ) {
		mkdir( $muPluginsDir, 0755, true );
	}

	/*
	 * Check if the loader file already exists, if not, create it
	 * The loader file is used to ensure that the BS Logger plugin is loaded before all other plugins
	 */
	if ( ! file_exists( $loaderFile ) ) {
		$fileContent = "<?php\n\n";
		$fileContent .= "/**\n";
		$fileContent .= " * Plugin Name: BS Logger Loader\n";
		$fileContent .= " * Description: Ensures that the BS Logger plugin is loaded before all other plugins\n";
		$fileContent .= " */\n";
		$fileContent .= "require_once WP_PLUGIN_DIR  . '/bs-logger/bs-logger.php';\n";

		file_put_contents( $loaderFile, $fileContent );
	}

	SMPLFY_Log::info( "Logger plugin has been activated" );
}
