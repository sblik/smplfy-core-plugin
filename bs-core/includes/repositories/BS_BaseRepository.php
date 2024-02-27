<?php

/**
 * This base repository serves as mechanism to perform CRUD operations on Gravity Forms entries
 * The BS_BaseRepository class has default methods for all repositories that extend it
 * @template T
 */
abstract class BS_BaseRepository {

	/**
	 * Override $entityType in repositories that extend this base repository
	 * @var class-string<T>
	 */
	protected static $entityType;

	/**
	 * The associated gravity-forms form id
	 */
	protected static $formId;

	/**
	 * Delete entry in Gravity Forms
	 *
	 * @param  mixed  $entryId
	 *
	 * @return bool Either true for success or a WP_Error instance.
	 */
	static function delete( $entryId ): bool {
		return GFAPI::delete_entry( $entryId );
	}

	/**
	 * Updates an entire single Entry object in Gravity Forms.
	 *
	 * @param  T  $entity
	 *
	 */
	static function update( $entity ): void {
		GFAPI::update_entry( $entity->formEntry );
	}

	/**
	 * Adds an entire single Entry object in Gravity Forms.
	 *
	 * @param  T  $entity
	 *
	 * @return int Either the new Entry ID or a WP_Error instance.
	 */
	static function add( $entity ): int {
		return GFAPI::add_entry( $entity->formEntry );
	}

	/**
	 * Get the first entry where the entry was created by the current user
	 *
	 * @return T|null
	 */
	static function get_one_for_current_user() {
		return self::get_one_for_user( get_current_user_id() );
	}

	/**
	 * Get the first entry where the entry created_by matches the provided user id
	 *
	 * @return T|null
	 */
	public static function get_one_for_user( $userId ) {
		return self::get_one( [ 'created_by' => $userId ] );
	}

	/**
	 * Get the first entry matching the filter
	 *
	 * @param $filters array of key value pairs
	 *
	 * @return T|null
	 */
	static function get_one( array $filters ) {
		try {
			$retrieved_entries = self::get( $filters );

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
	 * @param  array|null  $filters  array of key value pairs
	 * @param  string  $direction
	 * @param  null  $paging
	 *
	 * @return T[]
	 */
	private static function get( array $filters = null, string $direction = 'ASC', $paging = null ): array {
		$searchCriteria = array();

		if ( $filters != null ) {
			foreach ( $filters as $key => $value ) {
				$searchCriteria['field_filters'][] = array(
					'key'   => $key,
					'value' => $value,
				);
			}
		}

		$searchCriteria['status'] = 'active';

		$sorting = array(
			'key'        => 'id',
			'direction'  => $direction,
			'is_numeric' => true,
		);

		$retrieved_entries = GFAPI::get_entries( static::$formId, $searchCriteria, $sorting, $paging );

		if ( is_wp_error( $retrieved_entries ) ) {
			return array();
		}

		return self::map_to_entities( $retrieved_entries );
	}

	/**
	 * Maps form entries to entities associated with the repository
	 *
	 * @param $formEntries
	 *
	 * @return T[]
	 */
	private static function map_to_entities( $formEntries ): array {
		$entities = [];
		foreach ( $formEntries as $entry ) {
			$entities[] = new static::$entityType( $entry );
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
	static function get_one_by_id( $value ) {
		return self::get_one( [ 'id' => $value ] );
	}

	/**
	 * Get all entries where the filters match
	 *
	 * @param  array|null  $filters
	 * @param  string  $direction
	 *
	 * @return T[]
	 */
	public static function get_all( array $filters = null, string $direction = 'ASC' ): array {
		$paging = array( 'offset' => 0, 'page_size' => 999999999999 );

		return self::get( $filters, $direction, $paging );
	}
}