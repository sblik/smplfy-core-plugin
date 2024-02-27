<?php

use PHPUnit\Framework\TestCase;

require_once '../../bs-core/includes/repositories/BS_BaseRepository.php';
require_once '../../bs-core/includes/repositories/GravityFormsApiWrapper.php';

class TestConcreteRepository extends BS_BaseRepository {
	public function __construct( GravityFormsApiWrapper $gravityFormsApi ) {
		parent::__construct( $gravityFormsApi );
	}
}

class BS_BaseRepositoryTest extends TestCase {

	private $gravityFormsApiMock;
	private $repository;

	public function test_delete_returns_true_when_it_succeeds(): void {
		// Arrange
		$this->gravityFormsApiMock->method( 'delete_entry' )->willReturn( true );
		// Act
		$result = $this->repository->delete( 1 );
		// Assert
		$this->assertTrue( $result );
	}

	public function test_delete_returns_false_when_it_fails(): void {
		// Arrange
		$this->gravityFormsApiMock->method( 'delete_entry' )->willReturn( false );
		// Act
		$result = $this->repository->delete( 2 );
		// Assert
		$this->assertFalse( $result );
	}

	protected function setUp(): void {
		$this->gravityFormsApiMock = $this->createMock( GravityFormsApiWrapper::class );
		$this->repository          = new TestConcreteRepository( $this->gravityFormsApiMock );
	}
}
