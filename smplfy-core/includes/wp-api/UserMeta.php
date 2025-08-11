<?php

namespace SmplfyCore;


use WP_User;
use WP_User_Query;

class UserMeta {
	public static $phoneMetaRow = 'user_phone';

	/**
	 * @param $userID
	 * @param $metaKey
	 * @param $metaValue
	 *
	 * @return void
	 */
	public static function store_user_meta( $userID, $metaKey, $metaValue ) {
		delete_user_meta( $userID, $metaKey );
		add_user_meta( $userID, $metaKey, $metaValue );
		self::validate_add_user_meta( $userID, $metaKey, $metaValue );
	}

	/**
	 * @param $userID
	 * @param $metaKey
	 *
	 * @return mixed
	 */
	public static function retrieve_user_meta( $userID, $metaKey ) {
		return get_user_meta( $userID, $metaKey, true );
	}


	/**
	 * @param $metaKey
	 * @param $metaValue
	 *
	 * @return WP_User|null
	 */
	public static function get_user_by_meta( $metaKey, $metaValue ): ?WP_User {

		$args = [
			'meta_key'   => $metaKey,
			'meta_value' => $metaValue,
			'number'     => 1, // Limit to one result
		];

		$user_query = new WP_User_Query( $args );
		if ( ! empty( $user_query->get_results() ) ) {
			$users = $user_query->get_results();
			foreach ( $users as $user ) {
				return $user;
			}
		} else {
			return null;
		}

		return null;
	}

	/**
	 * @param $userID
	 * @param $metaKey
	 * @param $metaValue
	 *
	 * @return void
	 */
	private static function validate_add_user_meta( $userID, $metaKey, $metaValue ) {
		$returnedMetaValue = get_user_meta( $userID, $metaKey, $metaValue );
		if ( strval( $returnedMetaValue ) !== strval( $metaValue ) ) {
			SMPLFY_Log::error( "META VALUE NOT STORED! User ID: $userID Row that wasn't created: $metaKey" );
		}
	}
}