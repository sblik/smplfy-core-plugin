<?php

require_once __DIR__ . '/../../smplfy-core/includes/utilities/SMPLFY_Require.php';

use PHPUnit\Framework\TestCase;

class SMPLFY_RequireTest extends TestCase {

	private SMPLFY_Require $require;

	/**
	 * @group directory
	 */
	public function test_require_directory_correctly_requires_all_files(): void {
		// Arrange
		$dir = '/require-dependencies';
		// Act
		$this->require->directory( $dir );
		$result1 = test_dependency_one();
		$result2 = test_dependency_two();
		// Assert
		$this->assertEquals( 'test 1', $result1 );
		$this->assertEquals( 'test 2', $result2 );
	}

	public function test_directory_with_invalid_dir(): void {
		// Arrange
		$dir = '/invalidDir';
		// Act and Assert
		$this->expectException( Exception::class );
		$this->expectExceptionMessageMatches( '/^Directory not found:/' );
		$this->require->directory( $dir );
	}

	/**
	 * @group file
	 */
	public function test_require_file_correctly_requires_file(): void {
		// Arrange
		$filePath = '/TestDependency.php';
		// Act
		$this->require->file( $filePath );
		$result = ( new TestDependency() )->test_function();
		// Assert
		$this->assertEquals( 'test', $result );
	}

	public function test_require_file_with_invalid_file_throws_exception(): void {
		// Arrange
		$filePath = '/invalidFile.php';
		// Act and Assert
		$this->expectException( Exception::class );
		$this->expectExceptionMessageMatches( '/^File not found:/' );
		$this->require->file( $filePath );
	}

	protected function setUp(): void {
		$pluginDirectory = __DIR__;
		$this->require   = new SMPLFY_Require( $pluginDirectory );
	}
}