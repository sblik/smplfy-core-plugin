<?php
use SmplfyCore\SMPLFY_BaseRepository;
require_once __DIR__ . '/../../smplfy-core/includes/repositories/SMPLFY_BaseRepository.php';

class TestConcreteRepository extends SMPLFY_BaseRepository {
	public function __construct( SMPLFY_GravityFormsApiWrapper $gravityFormsApi ) {
		$this->formId     = 1;
		$this->entityType = TestConcreteEntity::class;
		parent::__construct( $gravityFormsApi );
	}
}