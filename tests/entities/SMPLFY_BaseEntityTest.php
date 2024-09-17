<?php

use PHPUnit\Framework\TestCase;
use SmplfyCore\SMPLFY_BaseEntity;
require_once __DIR__ . '/../../smplfy-core/includes/entities/SMPLFY_BaseEntity.php';
require_once __DIR__ . '/TestConcreteEntityBuilder.php';

class SMPLFY_BaseEntityTest extends TestCase {
	/**
	 * @group get_field_id
	 */
	public function test_get_field_id_with_existing_property_returns_id(): void {
		// Arrange
		$entity = ( new TestConcreteEntityBuilder() )->build_default();
		// Act
		$property1FieldId = $entity::get_field_id( 'property1' );
		$property2FieldId = $entity::get_field_id( 'property2' );
		$property3FieldId = $entity::get_field_id( 'property3' );
		// Assert
		$this->assertEquals( '1', $property1FieldId );
		$this->assertEquals( '2', $property2FieldId );
		$this->assertEquals( '3', $property3FieldId );
	}

	public function test_get_field_id_with_non_existing_property_returns_null(): void {
		// Arrange
		$entity = ( new TestConcreteEntityBuilder() )->build_default();
		// Act
		$property10FieldId = $entity::get_field_id( 'property10' );
		// Assert
		$this->assertNull( $property10FieldId );
	}

	/**
	 * @group get_set_property_value
	 */
	public function test_set_property_value_updates_mapped_form_entry_field(): void {
		// Arrange
		$formEntry   = [
			1 => 'value1',
			2 => 'value2',
			3 => 'value3',
		];
		$propertyMap = [
			'property1' => 1,
			'property2' => 2,
			'property3' => 3,
		];
		$entity      = ( new TestConcreteEntityBuilder() )
			->with_property_map( $propertyMap )
			->with_form_entry( $formEntry )
			->build();
		// Act
		$entity->property1 = 'new value';
		// Assert
		$this->assertEquals( 'new value', $entity->property1 );
		$this->assertEquals( 'new value', $entity->formEntry[1] );
	}

	public function test_set_property_value_that_is_not_mapped_throws_exception(): void {
		// Arrange
		$entity = ( new TestConcreteEntityBuilder() )->build();
		// Assert
		$this->expectException( Exception::class );
		// Act
		$entity->property10 = 'new value';
	}

	public function test_set_property_value_on_defined_class_property() {
		$entity = ( new TestConcreteEntityBuilder() )->build();
		// Act
		$entity->definedProperty = 'new value';
		// Assert
		$this->assertEquals( 'new value', $entity->definedProperty );
	}

	/**
	 * @group constructor
	 */
	public function test_constructor_stores_form_entry(): void {
		$formEntry  = array( 'field1' => 'value1', 'field2' => 'value2' );
		$baseEntity = new TestDefaultConcreteEntity( $formEntry );

		$this->assertEquals( $formEntry, $baseEntity->formEntry );
	}
}