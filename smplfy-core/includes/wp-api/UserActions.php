<?php

namespace SmplfyCore;

use WP_Error;
use WP_User;

class UserActions {

	/**
	 * @param $userID
	 *
	 * @return string|null
	 */
	public static function generate_password_link( $userID ) {
		$resetToken = get_password_reset_key( new WP_User( $userID ) );
		if(is_multisite()){
			$u  = get_userdata( $userID )->nickname;
		}else{
			$u  = get_userdata( $userID )->user_email;
		}

		if ( $resetToken instanceof WP_Error ) {
			return null;
		}

		return SITE_URL . '/login/?action=reset_password&mkey=' . $resetToken . '&u=' . $u;
	}

	public static function does_user_have_role( $user, $roleName ): bool {
		foreach ( $user->caps as $role => $true ) {
			if ( $role == $roleName ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $length
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function generate_password( $length = 12 ): string {
// Define the character sets
		$lowercase     = 'abcdefghijklmnopqrstuvwxyz';
		$uppercase     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$numbers       = '0123456789';
		$special_chars = '!@#$%^&*()_+-=[]{}|;:,.<>?';

// Combine all characters
		$all_chars = $lowercase . $uppercase . $numbers . $special_chars;

// Ensure at least one character from each set for security
		$password = $lowercase[ random_int( 0, strlen( $lowercase ) - 1 ) ];
		$password .= $uppercase[ random_int( 0, strlen( $uppercase ) - 1 ) ];
		$password .= $numbers[ random_int( 0, strlen( $numbers ) - 1 ) ];
		$password .= $special_chars[ random_int( 0, strlen( $special_chars ) - 1 ) ];

// Fill the rest with random characters from all sets
		for ( $i = 0; $i < $length - 4; $i ++ ) {
			$password .= $all_chars[ random_int( 0, strlen( $all_chars ) - 1 ) ];
		}

// Shuffle the password to ensure randomness
		return str_shuffle( $password );
	}

}