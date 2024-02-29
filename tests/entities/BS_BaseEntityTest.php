<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bs-core/includes/entities/BS_BaseEntity.php';
require_once __DIR__ . '/TestConcreteEntityBuilder.php';

class BS_BaseEntityTest extends TestCase {
	/**
	 * @group get_field_id
	 */
	public function test_get_field_id_with_existing_property(): void {
		// Arrange
		$formEntry = [
			'1' => 'value1',
			'2' => 'value2',
			'3' => 'value3',
		];
		$entity    = ( new TestConcreteEntityBuilder() )->with_form_entry( $formEntry )->build_default();
		// Act
		$property1FieldId = $entity::get_field_id( 'property1' );
		$property2FieldId = $entity::get_field_id( 'property2' );
		$property3FieldId = $entity::get_field_id( 'property3' );
		// Assert
		$this->assertEquals( '1', $property1FieldId );
		$this->assertEquals( '2', $property2FieldId );
		$this->assertEquals( '3', $property3FieldId );
	}
}