<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bs-core/includes/repositories/BS_BaseRepository.php';
require_once __DIR__ . '/../../bs-core/includes/repositories/GravityFormsApiWrapper.php';
require_once __DIR__ . '/TestConcreteRepository.php';
require_once __DIR__ . '/../fakes/Fake_WP_Error.php';

class BS_BaseRepositoryTest extends TestCase {
	private $gravityFormsApiMock;
	private TestConcreteRepository $repository;
	private stdClass $mockEntity;
	private int $formId = 1;

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

	/**
	 * @group get_one
	 */
	public function test_get_one_returns_null_when_nothing_matches_filters(): void {
		// Arrange
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( [ 'status' => 'active' ] ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [] );
		// Act
		$result = $this->repository->get_one( [] );
		// Assert
		$this->assertNull( $result );
	}

	public function test_get_one_returns_entity_when_something_matches_filters(): void {
		// Arrange
		$mockEntity1 = new stdClass();
		$filters     = [ 'id' => '1' ];
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [ $mockEntity1 ] );
		// Act
		$result = $this->repository->get_one( $filters );
		// Assert
		$this->assertEquals( $mockEntity1, $result );
	}

	public function test_get_one_returns_first_entity_when_multiple_entries_match_filters(): void {
		// Arrange
		$filters     = [ 'created_by' => 'jane' ];
		$mockEntity1 = new stdClass();
		$mockEntity2 = new stdClass();
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [ $mockEntity1, $mockEntity2 ] );
		// Act
		$result = $this->repository->get_one( $filters );
		// Assert
		$this->assertEquals( $mockEntity1, $result );
	}

	public function test_get_one_returns_matching_entity_when_multiple_filters_are_provided(): void {
		// Arrange
		$filters   = [ 'created_by' => 'bobby', '1' => 'United States' ];
		$mockEntry = new stdClass();
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [ $mockEntry ] );
		// Act
		$result = $this->repository->get_one( $filters );
		// Assert
		$this->assertEquals( $mockEntry, $result );
	}

	public function test_get_one_returns_null_when_api_throws_exception(): void {
		// Arrange
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->willThrowException( new Exception() );
		// Act
		$result = $this->repository->get_one( [ 'id' => '1' ] );
		// Assert
		$this->assertNull( $result );
	}

	protected function setUp(): void {
		$this->gravityFormsApiMock   = $this->createMock( GravityFormsApiWrapper::class );
		$this->repository            = new TestConcreteRepository( $this->gravityFormsApiMock );
		$this->mockEntity            = new stdClass();
		$this->mockEntity->formEntry = [];
	}
}


function get_expected_filters( $filters ): array {
	$expected_filters = [];
	foreach ( $filters as $key => $value ) {
		$expected_filters[] = [ 'key' => $key, 'value' => $value ];
	}

	return [
		'status'        => 'active',
		'field_filters' => $expected_filters,
	];
}