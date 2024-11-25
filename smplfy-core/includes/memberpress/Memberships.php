<?php

namespace SmplfyCore;
use WP_Error;
use MeprTransaction;
class Memberships {
	public static function does_user_have_membership( $userID, $membershipID ) {

		if ( class_exists( "MeprTransaction" ) ) {
			if ( ! empty( $userID ) ) {
				$userTransaction = MeprTransaction::get_all_by_user_id( $userID );

				foreach ( $userTransaction as $usrTxn ) {
					if ( $usrTxn->product_id == $membershipID ) {
						return true;
					}
				}
				return false;
			}
		} else {
			return new WP_Error( "class_missing", "MeprTransaction does not exist" );
		}
	}
}