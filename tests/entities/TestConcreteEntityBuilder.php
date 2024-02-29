<?php

require_once __DIR__ . '/TestConcreteEntity.php';

class TestConcreteEntityBuilder {
	private array $propertyMap;
	private array $formEntry;

	public function __construct() {
		$this->propertyMap = array();
		$this->formEntry   = array();
	}

	/**
	 * @param  array  $propertyMap
	 *
	 * @return $this
	 */
	public function with_property_map( array $propertyMap ): TestConcreteEntityBuilder {
		$this->propertyMap = $propertyMap;

		return $this;
	}

	/**
	 * @param  array  $formEntry
	 *
	 * @return $this
	 */
	public function with_form_entry( array $formEntry ): TestConcreteEntityBuilder {
		$this->formEntry = $formEntry;

		return $this;
	}

	public function build(): TestConcreteEntity {
		return new TestConcreteEntity( $this->propertyMap, $this->formEntry );
	}

	public function build_default(): TestDefaultConcreteEntity {
		return new TestDefaultConcreteEntity( $this->formEntry );
	}
}