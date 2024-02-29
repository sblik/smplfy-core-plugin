<?php

require_once __DIR__ . '/../../bs-core/includes/entities/BS_BaseEntity.php';

class TestConcreteEntity extends BS_BaseEntity {
	private array $propertyMap;

	public function __construct( $propertyMap = array(), $formEntry = array() ) {
		parent::__construct( $formEntry );
		$this->propertyMap = $propertyMap;
	}

	protected function get_property_map(): array {
		return $this->propertyMap;
	}
}

class TestDefaultConcreteEntity extends BS_BaseEntity {
	public function __construct( $formEntry = array() ) {
		parent::__construct( $formEntry );
	}

	protected function get_property_map(): array {
		return [
			'property1' => 1,
			'property2' => 2,
			'property3' => 3,
		];
	}
}