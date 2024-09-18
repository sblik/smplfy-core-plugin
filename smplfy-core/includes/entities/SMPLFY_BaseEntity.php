<?php
namespace SmplfyCore;
require_once __DIR__ . '/../gravity-forms/SMPLFY_GravityFormsKeys.php';

/**
 * This generic entity class can be extended as a way to automatically map the properties of a class to the keys of a gravity forms entry
 * By implementing the get_property_map method on your child class, you can define the mapping for example
 * protected function get_property_map(): array {
 *   return [
 *    'firstName' => '1.3',
 *    'lastName'  => '1.6',
 *    'userId'    => '7',
 *    'refererId' => '3',
 *   ];
 * }
 *
 * @property $id
 * @property $formId
 * @property $createdBy
 * @property $parentKey
 * @property $parentFormKey
 * @property $nestedFormFieldKey
 *
 */
abstract class SMPLFY_BaseEntity {
	/**
	 * Represents the gravity forms entry
	 * @var  $formEntry
	 */
	public $formEntry;

	public function __construct( $formEntry = array() ) {
		$this->formEntry = $formEntry;
	}

	/**
	 * Get the field id that is mapped to a specif property on the entity
	 *
	 * @param $property_name
	 *
	 * @return mixed|null
	 */
	public static function get_field_id( $property_name ) {
		// Get the name of the class the static method is called on
		$entityClass      = get_called_class();
		$instanceOfEntity = new $entityClass();

		$propertyMap = $instanceOfEntity->get_property_map();

		if ( array_key_exists( $property_name, $propertyMap ) ) {
			return $propertyMap[ $property_name ];
		}

		return null;
	}

	/**
	 * Used to return the array that represents the mapping between the properties and the form entry keys
	 * @return array
	 */
	protected abstract function get_property_map(): array;

	/**
	 * The __get method in PHP is a magic method that is automatically called when you try to access a property that is not accessible or does not exist in a class.
	 * In the context of the SMPLFY_BaseEntity class, the __get method is used to retrieve the value of a property from the formEntry array.
	 *
	 * @param $property
	 *
	 * @return mixed|null
	 */
	public function __get( $property ) {
		$propertyMap = $this->get_all_property_map();

		if ( array_key_exists( $property, $propertyMap ) ) {
			return $this->formEntry[ $propertyMap[ $property ] ];
		}

		return null;
	}

	/**
	 *
	 * The __set method in PHP is a magic method that is automatically called when you try to set a value to a property that is not accessible or does not exist in a class.
	 * In the context of the SMPLFY_BaseEntity class, the __set method is used to set the value of a property in the formEntry array.
	 *
	 * @param $property
	 * @param $value
	 *
	 * @return void
	 * @throws Exception
	 */
	public function __set( $property, $value ): void {
		$propertyMap = $this->get_all_property_map();

		try {
			$this->formEntry[ $propertyMap[ $property ] ] = $value;
		} catch ( Exception $e ) {
			throw new Exception( "Cannot set non-existing property: $property" );
		}
	}

	/**
	 * returns the default property mappings combined with the properties defined in the child class
	 * ref: https://docs.gravityforms.com/entry-object/
	 * @return string[]
	 */
	private function get_all_property_map(): array {
		$defaultProperties = array(
			'id'                 => 'id',
			'formId'             => 'form_id',
			'createdBy'          => 'created_by',
			'dateCreated'        => 'date_created',
			'dateUpdated'        => 'date_updated',
			'sourceUrl'          => 'source_url',
			'userAgent'          => 'user_agent',
			'parentKey'          => SMPLFYGravityFormsKeys::ENTRY_PARENT_KEY,
			'parentFormKey'      => SMPLFYGravityFormsKeys::ENTRY_PARENT_FORM_KEY,
			'nestedFormFieldKey' => SMPLFYGravityFormsKeys::ENTRY_NESTED_FORM_FIELD_KEY,
		);

		return array_merge( $this->get_property_map(), $defaultProperties );

	}
}