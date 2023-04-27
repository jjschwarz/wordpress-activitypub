<?php
namespace Activitypub;

use WP_Error;

/**
 * ActivityPub HTTP Class
 *
 * @author Matthias Pfefferle
 */
class Http {
	/**
	 * Send a POST Request with the needed HTTP Headers
	 *
	 * @param string $url     The URL endpoint
	 * @param string $body    The Post Body
	 * @param int    $user_id The WordPress User-ID
	 *
	 * @return array|WP_Error The POST Response or an WP_ERROR
	 */
	public static function post( $url, $body, $user_id ) {
		$date = \gmdate( 'D, d M Y H:i:s T' );
		$digest = Signature::generate_digest( $body );
		$signature = Signature::generate_signature( $user_id, 'post', $url, $date, $digest );

		$wp_version = \get_bloginfo( 'version' );
		$user_agent = \apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' ) );
		$args = array(
			'timeout' => 100,
			'limit_response_size' => 1048576,
			'redirection' => 3,
			'user-agent' => "$user_agent; ActivityPub",
			'headers' => array(
				'Accept' => 'application/activity+json',
				'Content-Type' => 'application/activity+json',
				'Digest' => "SHA-256=$digest",
				'Signature' => $signature,
				'Date' => $date,
			),
			'body' => $body,
		);

		$response = \wp_safe_remote_get( $url, $args );
		$code     = \wp_remote_retrieve_response_code( $response );

		if ( 400 >= $code && 500 <= $code  ) {
			$response = new WP_Error( $code, __( 'Failed HTTP Request', 'activitypub' ) );
		}

		\do_action( 'activitypub_safe_remote_post_response', $response, $url, $body, $user_id );

		return $response;
	}

		/**
	 * Send a GET Request with the needed HTTP Headers
	 *
	 * @param string $url     The URL endpoint
	 * @param int    $user_id The WordPress User-ID
	 *
	 * @return array|WP_Error The GET Response or an WP_ERROR
	 */
	public static function get( $url, $user_id ) {
		$date = \gmdate( 'D, d M Y H:i:s T' );
		$signature = Signature::generate_signature( $user_id, 'get', $url, $date );

		$wp_version = \get_bloginfo( 'version' );
		$user_agent = \apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' ) );
		$args = array(
			'timeout' => apply_filters( 'activitypub_remote_get_timeout', 100 ),
			'limit_response_size' => 1048576,
			'redirection' => 3,
			'user-agent' => "$user_agent; ActivityPub",
			'headers' => array(
				'Accept' => 'application/activity+json',
				'Content-Type' => 'application/activity+json',
				'Signature' => $signature,
				'Date' => $date,
			),
		);

		$response = \wp_safe_remote_get( $url, $args );
		$code     = \wp_remote_retrieve_response_code( $response );

		if ( 400 >= $code && 500 <= $code  ) {
			$response = new WP_Error( $code, __( 'Failed HTTP Request', 'activitypub' ) );
		}

		\do_action( 'activitypub_safe_remote_get_response', $response, $url, $user_id );

		return $response;
	}
}
