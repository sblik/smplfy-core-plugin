<?php

namespace SmplfyCore;

use WP_Error;

class WpHttpAPIHelper {
	/**
	 * Simplifies use of Wordpress' HTTP API for "wp_remote_post". Passing in false to $useDefaultArgs and passing in an array for $userArgs will override
	 * the use of default args. The default content-type is json and the array of the request body is encoded before sending the post
	 *
	 * @param $url
	 * @param array $requestBody
	 * @param bool $useDefaultArgs
	 * @param null $userArgs
	 *
	 * @return array|WP_Error
	 */
	public static function send_remote_post( $url, array $requestBody, bool $useDefaultArgs = true, $userArgs = null ) {
		$requestBody = json_encode( $requestBody );
		if ( $useDefaultArgs ) {
			$args = self::generate_wp_post_args( $requestBody );
		} else {
			$userArgs['body'] = $requestBody;
			$args             = $userArgs;
		}

		$response = wp_remote_post( $url, $args );

		return $response['response'];

	}

	/**
	 * @param $requestBody
	 * @param $userArgs
	 *
	 * @return array
	 */
	private static function generate_wp_post_args( $requestBody, $userArgs = null ): array {
		return array(
			'body'        => $requestBody,
			'timeout'     => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array( 'accept' => 'application/json', 'content-type' => 'application/json' ),
			'cookies'     => array(),
		);
	}


}