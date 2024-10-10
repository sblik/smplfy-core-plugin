<?php
namespace SmplfyCore;
use Gravity_Flow_API;
class WorkflowStep {

	public static function send(int $stepId, $entry){
		$formId = $entry['form_id'];

		$api     = new Gravity_Flow_API( $formId );
		$api->send_to_step( $entry, $stepId );
	}

}