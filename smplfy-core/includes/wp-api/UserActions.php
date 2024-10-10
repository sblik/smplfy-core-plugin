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
		$userEmail  = get_userdata( $userID )->user_email;
		if ( $resetToken instanceof WP_Error ) {
			return null;
		}

		return SITE_URL . '/login/?action=reset_password&mkey=' . $resetToken . '&u=' . $userEmail;
	}

	public static function does_user_have_role( $user, $roleName ): bool {
		foreach ( $user->caps as $role => $true ) {
			if ( $role == $roleName ) {
				return true;
			}
		}

		return false;
	}

}