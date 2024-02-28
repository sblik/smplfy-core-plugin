<?php

class TestConcreteRepository extends BS_BaseRepository {
	public function __construct( GravityFormsApiWrapper $gravityFormsApi ) {
		$this->formId     = 1;
		$this->entityType = stdClass::class;
		parent::__construct( $gravityFormsApi );
	}
}