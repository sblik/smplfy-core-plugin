<?php
namespace SmplfyCore;
use GFAPI;
use WP_Error;

class SMPLFY_GravityFormsApiWrapper {
	/**
	 * @param $entryId
	 *
	 * @return bool|WP_Error
	 */
	public function delete_entry( $entryId ) {
		return GFAPI::delete_entry( $entryId );
	}

	/**
	 * @param $entry
	 * @param $entryId
	 *
	 * @return true|WP_Error
	 */
	public function update_entry( $entry, $entryId = null ) {
		return GFAPI::update_entry( $entry, $entryId );
	}

	/**
	 * @param $entry
	 *
	 * @return int|WP_Error
	 */
	public function add_entry( $entry ) {
		return GFAPI::add_entry( $entry );
	}

	/**
	 * @param  $form_ids
	 * @param  array  $search_criteria
	 * @param  null  $sorting
	 * @param  null  $paging
	 * @param  null  $total_count
	 *
	 * @return array|WP_Error
	 */
	public function get_entries( $form_ids, array $search_criteria = array(), $sorting = null, $paging = null, &$total_count = null ) {
		return GFAPI::get_entries( $form_ids, $search_criteria, $sorting, $paging, $total_count );
	}

	/**
	 * @param  $form_ids
	 * @param  array  $search_criteria
	 *
	 * @return int
	 */
	public function count_entries( $form_ids, array $search_criteria = array() ) : int {
		return GFAPI::count_entries( $form_ids, $search_criteria );
	}
}