<?php

namespace SmplfyCore;


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