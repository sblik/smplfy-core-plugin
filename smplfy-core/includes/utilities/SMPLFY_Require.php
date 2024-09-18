<?php
namespace SmplfyCore;
class SMPLFY_Require {

	private string $pluginDirectory;

	public function __construct( string $pluginDirectory ) {

		$this->pluginDirectory = $pluginDirectory;
	}

	/**
	 * recursively require all php files in a directory
	 *
	 * @param $dir
	 *
	 * @return void
	 * @throws Exception
	 */
	function directory( $dir ) {
		if ( ! realpath( $dir ) ) {
			$dir = $this->pluginDirectory . $dir; // Append plugin dir if it is a relative path
		}

		if ( ! is_dir( $dir ) ) {
			throw new Exception( "Directory not found: $dir" );
		}

		$items = glob( $dir . '/*' );

		foreach ( $items as $path ) {
			$isFile = preg_match( '/\.php$/', $path );

			if ( $isFile ) {
				require_once $path;
			} elseif ( is_dir( $path ) ) {
				$this->directory( $path );
			}
		}
	}

	/**
	 * require a single file
	 *
	 * @param $filePath
	 *
	 * @return void
	 * @throws Exception
	 */
	function file( $filePath ) {
		$file = $this->pluginDirectory . $filePath;

		if ( ! file_exists( $file ) ) {
			throw new Exception( "File not found: $file" );
		}

		require_once $file;
	}
}