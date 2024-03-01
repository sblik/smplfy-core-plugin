<?php

require_once __DIR__ . '/../../smp-core/includes/entities/SMP_BaseEntity.php';

class TestConcreteEntity extends SMP_BaseEntity {
	public string $definedProperty;
	private array $propertyMap;

	public function __construct( $propertyMap = array(), $formEntry = array() ) {
		parent::__construct( $formEntry );
		$this->propertyMap = $propertyMap;
	}

	protected function get_property_map(): array {
		return $this->propertyMap;
	}
}

class TestDefaultConcreteEntity extends SMP_BaseEntity {
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