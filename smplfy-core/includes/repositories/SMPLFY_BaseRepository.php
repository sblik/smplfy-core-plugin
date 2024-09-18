<?php

/**
 * This base repository serves as mechanism to perform CRUD operations on Gravity Forms entries
 * The SMPLFY_BaseRepository class has default methods for all repositories that extend it
 * @template T
 */
namespace SmplfyCore;
abstract class SMPLFY_BaseRepository {
	/**
	 * Override $entityType in repositories that extend this base repository
	 * @var class-string<T>
	 */
	protected $entityType;

	/**
	 * The associated gravity-forms form id
	 */
	protected $formId;

	/**
	 * Holds wrapper to gravity forms api
	 */
	protected SMPLFY_GravityFormsApiWrapper $gravityFormsApi;

	public function __construct( SMPLFY_GravityFormsApiWrapper $gravityFormsApi ) {
		$this->gravityFormsApi = $gravityFormsApi;
	}

	/**
	 * Delete entry in Gravity Forms
	 *
	 * @param  mixed  $entryId
	 *
	 * @return bool|WP_Error Either true for success or a WP_Error instance.
	 */
	function delete( $entryId ) {
		return $this->gravityFormsApi->delete_entry( $entryId );
	}

	/**
	 * Updates an entire single Entry object in Gravity Forms.
	 *
	 * @param  T  $entity
	 *
	 * @return true|WP_Error
	 */
	function update( $entity ) {
		return $this->gravityFormsApi->update_entry( $entity->formEntry );
	}

	/**
	 * Adds an entire single Entry object in Gravity Forms.
	 *
	 * @param  T  $entity
	 *
	 * @return int|WP_Error Either the new Entry ID or a WP_Error instance.
	 */
	function add( $entity ) {
		return $this->gravityFormsApi->add_entry( $entity->formEntry );
	}

	/**
	 * Get the first entry where the entry was created by the current user
	 *
	 * @return T|null
	 */
	function get_one_for_current_user() {
		return $this->get_one_for_user( get_current_user_id() );
	}

	/**
	 * Get the first entry where the entry created_by matches the provided user id
	 *
	 * @return T|null
	 */
	public function get_one_for_user( $userId ) {
		return $this->get_one( [ 'created_by' => $userId ] );
	}

	/**
	 * Get the first entry matching the filter
	 *
	 * @param $filters array of key value pairs e.g. ['id' => $value, 'created_by' => $userId]
	 *
	 * @return T|null
	 */
	public function get_one( array $filters ) {
		try {
			$retrieved_entries = $this->get( $filters );

			if ( ! empty( $retrieved_entries ) ) {
				return $retrieved_entries[0];
			} else {
				return null;
			}

		} catch ( Exception $ex ) {
			return null;
		}
	}

	/**
	 * Generic get method used by both get_one and get_all
	 *
	 * @param  array|null  $filters  an array of key value pairs e.g. ['id' => $value, 'created_by' => $userId]
	 * @param  string  $direction
	 * @param  null  $paging
	 *
	 * @return T[]
	 */
	private function get( array $filters = null, string $direction = 'ASC', $paging = null ): array {

		$sorting = array(
			'key'        => 'id',
			'direction'  => $direction,
			'is_numeric' => true,
		);

		$retrieved_entries = $this->gravityFormsApi->get_entries( $this->formId, $this->get_search_Criteria($filters), $sorting, $paging );

		if ( is_wp_error( $retrieved_entries ) ) {
			return array();
		}

		return $this->map_to_entities( $retrieved_entries );
	}

	/**
	 * Maps form entries to entities associated with the repository
	 *
	 * @param $formEntries
	 *
	 * @return T[]
	 */
	private function map_to_entities( $formEntries ): array {
		$entities = [];
		foreach ( $formEntries as $entry ) {
			$entities[] = new $this->entityType( $entry );
		}

		return $entities;
	}

	/**
	 * Get the first entry where the id matches the given value
	 *
	 * @param $value
	 *
	 * @return T|null
	 */
	public function get_one_by_id( $value ) {
		return $this->get_one( [ 'id' => $value ] );
	}

	/**
	 * Get all entries where the filters match
	 *
	 * @param  array|null  $filters  an array of key value pairs e.g. ['id' => $value, 'created_by' => $userId]
	 * @param  string  $direction
	 *
	 * @return T[]
	 */
	public function get_all( array $filters = null, string $direction = 'ASC' ): array {
		$paging = array( 'offset' => 0, 'page_size' => 999999999999 );

		return $this->get( $filters, $direction, $paging );
	}

	/**
	 * Get all entries between start and end date
	 *
	 * @param  string  $startDate
	 * @param  string  $endDate
	 * @param  array|null  $filters  an array of key value pairs e.g. ['id' => $value, 'created_by' => $userId]
	 * @param  string  $direction
	 *
	 * @return T[]
	 */
	public function get_all_between( string $startDate, string $endDate, array $filters = null, string $direction = 'ASC' ): array {
		$paging = array( 'offset' => 0, 'page_size' => 999999999999 );

		$filters['start_date'] = $startDate;
		$filters['end_date']   = $endDate;

		return $this->get( $filters, $direction, $paging );
	}

	/**
	 * Get total number of entries matching the filters
	 * @param  array[]  $filters
	 *
	 * @return int
	 */
	public function count_entries( array $filters = null ): int {

		return $this->gravityFormsApi->count_entries( $this->formId, $this->get_search_Criteria( $filters ));
	}

	/**
	 * Generic get method used by both get and count_entries to return search criteria
	 *
	 * @param array|null $filters  an array of key value pairs e.g. ['id' => $value, 'created_by' => $userId]
	 *
	 * @return array
	 */
	private function get_search_Criteria( array $filters = null): array{

		$searchCriteria = array();

		if ( $filters != null ) {
			foreach ( $filters as $key => $value ) {

				if ( $key == 'start_date' ) {
					$searchCriteria['start_date'] = $value;
					continue;
				}
				if ( $key == 'end_date' ) {
					$searchCriteria['end_date'] = $value;
					continue;
				}

				$searchCriteria['field_filters'][] = array(
					'key'   => $key,
					'value' => $value,
				);
			}
		}

		$searchCriteria['status'] = 'active';

		return $searchCriteria;
	}
}

