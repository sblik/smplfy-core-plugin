<?php

use PHPUnit\Framework\TestCase;
use SmplfyCore\SMPLFY_GravityFormsApiWrapper;
require_once __DIR__ . '/../../smplfy-core/includes/gravity-forms/SMPLFY_GravityFormsApiWrapper.php';
require_once __DIR__ . '/../entities/TestConcreteEntity.php';
require_once __DIR__ . '/TestConcreteRepository.php';

class SMPLFY_BaseRepositoryTest extends TestCase {
	private $gravityFormsApiMock;
	private TestConcreteRepository $repository;
	private TestConcreteEntity $entity;
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

	public function test_delete_throws_error_when_api_throws(): void {
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
		$result = $this->repository->add( $this->entity );
		// Assert
		$this->assertEquals( $mockId, $result );
	}

	public function add_throws_error_when_api_throws(): void {
		// Arrange
		$errorCode    = 'error';
		$errorMessage = 'API Error';
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'add_entry' )
		                          ->willReturn( new WP_Error( $errorCode, $errorMessage ) );
		// Act
		$result = $this->repository->add( $this->entity );
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
		                          ->with( $this->equalTo( $this->entity->formEntry ) )
		                          ->willReturn( true );
		// Act
		$result = $this->repository->update( $this->entity );
		// Assert
		$this->assertTrue( $result );
	}

	public function test_update_returns_false_when_unsuccessful(): void {
		// Arrange
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'update_entry' )
		                          ->with( $this->equalTo( $this->entity->formEntry ) )
		                          ->willReturn( false );
		// Act
		$result = $this->repository->update( $this->entity );
		// Assert
		$this->assertFalse( $result );
	}

	public function test_update_returns_wp_error_when_api_throws(): void {
		// Arrange
		$errorCode    = 'error';
		$errorMessage = 'API Error';
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'update_entry' )
		                          ->with( $this->equalTo( $this->entity->formEntry ) )
		                          ->willReturn( new WP_Error( $errorCode, $errorMessage ) );
		// Act
		$result = $this->repository->update( $this->entity );
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
		$entity1 = new TestConcreteEntity();
		$filters = [ 'id' => '1' ];
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [ $entity1->formEntry ] );
		// Act
		$result = $this->repository->get_one( $filters );
		// Assert
		$this->assertEquals( $entity1, $result );
	}

	public function test_get_one_returns_first_entity_when_multiple_entries_match_filters(): void {
		// Arrange
		$filters = [ 'created_by' => 'jane' ];
		$entity1 = new TestConcreteEntity();
		$entity2 = new TestConcreteEntity();
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [ $entity1->formEntry, $entity2->formEntry ] );
		// Act
		$result = $this->repository->get_one( $filters );
		// Assert
		$this->assertEquals( $entity1, $result );
	}

	public function test_get_one_returns_matching_entity_when_multiple_filters_are_provided(): void {
		// Arrange
		$filters = [ 'created_by' => 'bobby', '1' => 'United States' ];
		$entity1 = new TestConcreteEntity();
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [ $entity1->formEntry ] );
		// Act
		$result = $this->repository->get_one( $filters );
		// Assert
		$this->assertEquals( $entity1, $result );
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

	/**
	 * @group get_all
	 */
	public function test_get_all_returns_entities_when_entries_match_filters(): void {
		// Arrange
		$entity1 = new TestConcreteEntity();
		$entity2 = new TestConcreteEntity();
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( [ 'status' => 'active' ] ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [ $entity1->formEntry, $entity2->formEntry ] );
		// Act
		$result = $this->repository->get_all( [] );
		// Assert
		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertEquals( $entity1, $result[0] );
		$this->assertEquals( $entity2, $result[1] );
	}

	public function test_get_all_with_start_and_end_date_queries_gravity_forms_correctly(): void {
		// Arrange
		$startDate = '2024-03-01';
		$endDate   = '2024-03-22';
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( [
				                          'status'     => 'active',
				                          'start_date' => $startDate,
				                          'end_date'   => $endDate,
			                          ] ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [] );
		// Act
		$result = $this->repository->get_all( [ 'start_date' => $startDate, 'end_date' => $endDate ] );
		// Assert
		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	public function test_get_all_returns_empty_array_when_no_entries_match_filters(): void {
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
		$result = $this->repository->get_all( [] );
		// Assert
		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	public function test_get_all_returns_empty_array_when_api_throws_exception(): void {
		// Arrange
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->willReturn( new WP_Error( 'error', 'error message' ) );
		// Act
		$result = $this->repository->get_all( [] );
		// Assert
		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	/**
	 * @group get_all_between
	 */
	public function test_get_all_between_queries_gravity_forms_correctly(): void {
		// Arrange
		$startDate = '2024-03-05';
		$endDate   = '2024-03-22';
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( [
				                          'status'     => 'active',
				                          'start_date' => $startDate,
				                          'end_date'   => $endDate,
			                          ] ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [] );
		// Act
		$result = $this->repository->get_all_between( $startDate, $endDate );
		// Assert
		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	/**
	 * @group get_one_by_id
	 */
	public function test_get_one_by_id_returns_null_when_nothing_matches_filters(): void {
		// Arrange
		$filters = [ 'id' => '1' ];
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [] );
		// Act
		$result = $this->repository->get_one_by_id( '1' );
		// Assert
		$this->assertNull( $result );
	}

	public function test_get_one_by_id_returns_entity_when_something_matches_filters(): void {
		// Arrange
		$entity1 = new TestConcreteEntity();
		$filters = [ 'id' => '1' ];
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [ $entity1->formEntry ] );
		// Act
		$result = $this->repository->get_one_by_id( '1' );
		// Assert
		$this->assertEquals( $entity1, $result );
	}

	/**
	 * @group get_one_for_user
	 */
	public function test_get_one_for_user_returns_null_when_nothing_matches_filters(): void {
		// Arrange
		$filters = [ 'created_by' => 1 ];
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [] );
		// Act
		$result = $this->repository->get_one_for_user( 1 );
		// Assert
		$this->assertNull( $result );
	}

	public function test_get_one_for_user_returns_entity_when_something_matches_filters(): void {
		// Arrange
		$entity1 = new TestConcreteEntity();
		$filters = [ 'created_by' => 1 ];
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [ $entity1->formEntry ] );
		// Act
		$result = $this->repository->get_one_for_user( 1 );
		// Assert
		$this->assertEquals( $entity1, $result );
	}

	/**
	 * @group get_one_for_current_user
	 */
	public function test_get_one_for_current_user_returns_null_when_nothing_matches_filters(): void {
		// Arrange
		$filters = [ 'created_by' => 1 ];
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [] );
		// Act
		$result = $this->repository->get_one_for_current_user();
		// Assert
		$this->assertNull( $result );
	}

	public function test_get_one_for_current_user_returns_entity_when_something_matches_filters(): void {
		// Arrange
		$entity1 = new TestConcreteEntity();
		$filters = [ 'created_by' => 1 ];
		$this->gravityFormsApiMock->expects( $this->once() )
		                          ->method( 'get_entries' )
		                          ->with(
			                          $this->equalTo( $this->formId ),
			                          $this->equalTo( get_expected_filters( $filters ) ),
			                          $this->anything(),
			                          $this->anything()
		                          )
		                          ->willReturn( [ $entity1->formEntry ] );
		// Act
		$result = $this->repository->get_one_for_current_user();
		// Assert
		$this->assertEquals( $entity1, $result );
	}

	protected function setUp(): void {
		$this->gravityFormsApiMock = $this->createMock( SMPLFY_GravityFormsApiWrapper::class );
		$this->repository          = new TestConcreteRepository( $this->gravityFormsApiMock );
		$this->entity              = new TestConcreteEntity();
		$this->entity->formEntry   = [];
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