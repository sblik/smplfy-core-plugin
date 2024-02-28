<?php

require_once __DIR__ . '/../../bs-core/includes/repositories/BS_BaseRepository.php';

class TestConcreteRepository extends BS_BaseRepository {
	public function __construct( GravityFormsApiWrapper $gravityFormsApi ) {
		$this->formId     = 1;
		$this->entityType = TestConcreteEntity::class;
		parent::__construct( $gravityFormsApi );
	}
}