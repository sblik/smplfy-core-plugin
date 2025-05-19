<?php

namespace SmplfyCore;
class DisplayUserInfoInView {

	public static function get_user_info_to_display( $wordToExtractLookupValue, $contentFromView, $contentToReturn ) {
		if ( preg_match( "/$wordToExtractLookupValue(.{1,4})/", $contentFromView, $matches ) ) {
			$userID = $matches[1];
		} elseif ( preg_match( "/$wordToExtractLookupValue(.{1,50})/", $contentFromView, $matches ) ) {
			$userEmail = $matches[1];
			$userID    = get_user_by_email( $userEmail )->ID;
		}

		SMPLFY_Log::info( "CONTENT TO RETURN: ", $contentToReturn );


		if ( ! empty( $userID ) ) {
			$user             = get_user_by( 'ID', $userID );
			$name             = $user->first_name . ' ' . $user->last_name;
			$phone            = UserMeta::retrieve_user_meta( $user->ID, 'mepr_phone' );
			$address          = UserMeta::retrieve_user_meta( $userID, 'mepr_street_1' ) . ', ' . UserMeta::retrieve_user_meta( $userID, 'mepr-address-city' ) . ', ' . UserMeta::retrieve_user_meta( $userID, 'mepr-address-state' ) . ', ' . UserMeta::retrieve_user_meta( $userID, 'mepr-address-country' );
			$addressPopulated = preg_match( '/^[, ]+$/', $address ) === 1;

			if ( ! empty( $name ) ) {
				$contentToReturn = str_replace( "*NAME*", $name, $contentToReturn );
			}
			$contentToReturn = str_replace( "*EMAIL*", $user->user_email, $contentToReturn );
			if ( ! empty( $phone ) ) {
				$contentToReturn = str_replace( "*PHONE*", $phone, $contentToReturn );
			}
			if ( ! $addressPopulated ) {
				$contentToReturn = str_replace( "*ADDRESS*", $addressPopulated, $contentToReturn );
			}
		}
		SMPLFY_Log::info( "CONTENT TO RETURN AT END: ", $contentToReturn );

		return $contentToReturn;

	}
}