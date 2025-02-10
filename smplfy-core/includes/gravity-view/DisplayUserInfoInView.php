<?php
namespace SmplfyCore;
class DisplayUserInfoInView {

	public static function get_user_info_to_display($word, $type, $content, $toReturn = null) {


		if($type == 1){
			if ( preg_match( "/$word(.{1,4})/", $content, $matches ) ) {
				$userID = $matches[1];
			}
		}elseif($type == 2){
			if ( preg_match( "/$word(.{1,50})/", $content, $matches ) ) {
				$userEmail = $matches[1];
				$userID    = get_user_by_email( $userEmail )->ID;
			}
		}


		if ( ! empty( $userID ) ) {
			$user             = get_user_by( 'ID', $userID );
			$name             = $user->first_name . ' ' . $user->last_name;
			$phone            = UserMeta::retrieve_user_meta( $user->ID, 'mepr_phone' );
			$address          = UserMeta::retrieve_user_meta( $userID, 'mepr_street_1' ) . ', ' . UserMeta::retrieve_user_meta( $userID, 'mepr-address-city' ) . ', ' . UserMeta::retrieve_user_meta( $userID, 'mepr-address-state' ) . ', ' . UserMeta::retrieve_user_meta( $userID, 'mepr-address-country' );
			$addressPopulated = preg_match( '/^[, ]+$/', $address ) === 1;
			$break            = "<br>";

			$contentToReturn = '';

			if ( ! empty( $name ) ) {
				$contentToReturn = $contentToReturn . "Name: $name $break";
			}
			$contentToReturn = $contentToReturn . "Email: $user->user_email $break";
			if ( ! empty( $phone ) ) {
				$contentToReturn = $contentToReturn . "Phone: $phone $break";
			}
			if ( ! $addressPopulated ) {
				$contentToReturn = $contentToReturn . "Address: $address $break";
			}
		}

	}
}