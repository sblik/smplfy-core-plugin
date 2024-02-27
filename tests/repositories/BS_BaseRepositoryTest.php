<?php

use PHPUnit\Framework\TestCase;

require_once '../../bs-core/includes/repositories/BS_BaseRepository.php';
require_once '../../bs-core/includes/repositories/GravityFormsApiWrapper.php';
require_once getenv( 'WP_PATH' ) . '/wp-includes/class-wp-error.php';
require_once getenv( 'WP_PATH' ) . '/wp-includes/plugin.php';

class TestConcreteRepository extends BS_BaseRepository {
	public function __construct( GravityFormsApiWrapper $gravityFormsApi ) {
		parent::__construct( $gravityFormsApi );
	}
}

class BS_BaseRepositoryTest extends TestCase {
	private $gravityFormsApiMock;
	private TestConcreteRepository $repository;
	private stdClass $mockEntity;

	/**
	 * @group delete
	 */
	public function test_delete_returns_true_when_it_succeeds(): void {
		// Arrange
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'delete_entry' )
		                          ->willReturn( true );
		// Act
		$result = $this->repository->delete( 1 );
		// Assert
		$this->assertTrue( $result );
	}

	public function test_delete_returns_false_when_it_fails(): void {
		// Arrange
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'delete_entry' )
		                          ->willReturn( false );
		// Act
		$result = $this->repository->delete( 2 );
		// Assert
		$this->assertFalse( $result );
	}

	public function test_delete_throws_exception_when_api_throws(): void {
		// Arrange
		$errorCode    = 'error';
		$errorMessage = 'API Error';
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'delete_entry' )
		                          ->willReturn( new WP_Error( $errorCode, $errorMessage ) );
		// Act
		$result = $this->repository->delete( 3 );
		// Assert
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( $errorCode, $result->get_error_code() );
		$this->assertEquals( $errorMessage, $result->get_error_message() );
	}

	/**
	 * @group add
	 */
	public function add_returns_id_when_successful(): void {
		// Arrange
		$mockId = 1;
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'add_entry' )
		                          ->willReturn( $mockId );
		// Act
		$result = $this->repository->add( $this->mockEntity );
		// Assert
		$this->assertEquals( $mockId, $result );
	}

	public function add_throws_exception_when_api_throws(): void {
		// Arrange
		$errorCode    = 'error';
		$errorMessage = 'API Error';
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'add_entry' )
		                          ->willReturn( new WP_Error( $errorCode, $errorMessage ) );
		// Act
		$result = $this->repository->add( $this->mockEntity );
		// Assert
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( $errorCode, $result->get_error_code() );
		$this->assertEquals( $errorMessage, $result->get_error_message() );
	}

	/**
	 * @group update
	 */
	public function test_update_returns_true_when_successful(): void {
		// Arrange
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'update_entry' )
		                          ->with( $this->equalTo( $this->mockEntity->formEntry ) )
		                          ->willReturn( true );
		// Act
		$result = $this->repository->update( $this->mockEntity );
		// Assert
		$this->assertTrue( $result );
	}

	public function test_update_returns_false_when_unsuccessful(): void {
		// Arrange
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'update_entry' )
		                          ->with( $this->equalTo( $this->mockEntity->formEntry ) )
		                          ->willReturn( false );
		// Act
		$result = $this->repository->update( $this->mockEntity );
		// Assert
		$this->assertFalse( $result );
	}

	public function test_update_returns_wp_error_when_api_throws(): void {
		// Arrange
		$errorCode    = 'error';
		$errorMessage = 'API Error';
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'update_entry' )
		                          ->with( $this->equalTo( $this->mockEntity->formEntry ) )
		                          ->willReturn( new WP_Error( $errorCode, $errorMessage ) );
		// Act
		$result = $this->repository->update( $this->mockEntity );
		// Assert
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( $errorCode, $result->get_error_code() );
		$this->assertEquals( $errorMessage, $result->get_error_message() );
	}

	protected function setUp(): void {
		$this->gravityFormsApiMock   = $this->createMock( GravityFormsApiWrapper::class );
		$this->repository            = new TestConcreteRepository( $this->gravityFormsApiMock );
		$this->mockEntity            = new stdClass();
		$this->mockEntity->formEntry = [];
	}
}
