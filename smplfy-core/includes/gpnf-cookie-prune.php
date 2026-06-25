<?php
/**
 * Prune stale GP Nested Forms session cookies.
 *
 * GP Nested Forms sets one `gpnf_form_session_*` cookie per form/endpoint and lets
 * them live for a long time. On sites with many nested-form pages these accumulate
 * until the combined Cookie request header exceeds Apache's `LimitRequestFieldSize`
 * (~8 KB), producing intermittent `400 Bad Request` errors on navigation.
 *
 * On every request we look for these cookies, read the creation timestamp out of the
 * (URL-decoded) JSON payload, and expire any that are older than a short TTL. Cookies
 * still within the TTL are left alone so in-progress nested-form entries survive.
 *
 * The fix lives entirely in smplfy-core; GP Nested Forms files are never touched.
 *
 * @package SMP Core
 */

namespace SmplfyCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prefix every GP Nested Forms session cookie shares.
 */
const SMPLFY_GPNF_COOKIE_PREFIX = 'gpnf_form_session_';

/**
 * Candidate keys that may hold the cookie's creation time, most likely first.
 *
 * NOTE: The exact key should be confirmed against a live `gpnf_form_session_*`
 * cookie (decode the URL-encoded JSON that begins with `{"form_id":...`). GP Nested
 * Forms has historically used `created`; we also accept a few synonyms so a version
 * difference cannot silently fail the TTL check and expire fresh cookies. If none of
 * these keys are present we fall back to the JSON `expires` value before failing safe.
 */
const SMPLFY_GPNF_CREATED_KEYS = array( 'created', 'timestamp', 'created_at', 'time' );

/**
 * On each request, expire stale GP Nested Forms session cookies.
 *
 * Registered on `init` at priority 1 so it runs before code that reads the session.
 *
 * @return void
 */
function smplfy_gpnf_prune_stale_session_cookies() {
	// Nothing to do, or it is already too late to send Set-Cookie headers.
	if ( empty( $_COOKIE ) || headers_sent() ) {
		return;
	}

	$ttl = defined( 'SMPLFY_GPNF_COOKIE_TTL' ) ? (int) SMPLFY_GPNF_COOKIE_TTL : HOUR_IN_SECONDS;
	$now = time();

	foreach ( $_COOKIE as $name => $value ) {
		if ( strpos( $name, SMPLFY_GPNF_COOKIE_PREFIX ) !== 0 ) {
			continue;
		}

		if ( ! smplfy_gpnf_cookie_is_stale( $value, $now, $ttl ) ) {
			// Within TTL (or expires in the future): leave it, protecting in-progress entries.
			continue;
		}

		smplfy_gpnf_expire_cookie( $name );

		// Drop it for the remainder of this request so nothing reuses the stale value.
		unset( $_COOKIE[ $name ] );
	}
}

/**
 * Decide whether a single GP Nested Forms session cookie value is stale.
 *
 * Decodes the (URL-decoded) JSON payload and compares its creation timestamp against
 * the TTL. If the timestamp cannot be read at all we treat the cookie as stale so a
 * malformed / unexpected cookie never lingers (fail safe).
 *
 * @param string $value Raw cookie value from `$_COOKIE`.
 * @param int    $now   Current Unix timestamp.
 * @param int    $ttl   Maximum age in seconds before a cookie is considered stale.
 *
 * @return bool True if the cookie should be expired.
 */
function smplfy_gpnf_cookie_is_stale( $value, $now, $ttl ) {
	$data = smplfy_gpnf_decode_cookie( $value );

	// Could not decode JSON at all -> fail safe and expire it.
	if ( ! is_array( $data ) ) {
		return true;
	}

	$created = smplfy_gpnf_first_numeric( $data, SMPLFY_GPNF_CREATED_KEYS );
	if ( $created !== null ) {
		return ( $now - $created ) > $ttl;
	}

	// No creation key found. Fall back to GP Nested Forms' own `expires` value if present
	// so we only prune cookies that have genuinely lapsed (never a fresh, in-progress one).
	if ( isset( $data['expires'] ) && is_numeric( $data['expires'] ) ) {
		return (int) $data['expires'] <= $now;
	}

	// No usable timestamp anywhere -> fail safe and expire it.
	return true;
}

/**
 * Decode a GP Nested Forms session cookie value into an array.
 *
 * PHP already URL-decodes `$_COOKIE` values, but the payload is sometimes still
 * percent-encoded (and may carry magic-quote slashes), so normalise both before
 * decoding the JSON.
 *
 * @param string $value Raw cookie value.
 *
 * @return array|null Decoded payload, or null if it is not valid JSON.
 */
function smplfy_gpnf_decode_cookie( $value ) {
	if ( ! is_string( $value ) || '' === $value ) {
		return null;
	}

	$raw = $value;

	// Still percent-encoded (e.g. "%7B...") rather than raw JSON ("{...}")? Decode it.
	if ( false === strpos( $raw, '{' ) && false !== strpos( $raw, '%' ) ) {
		$raw = urldecode( $raw );
	}

	$raw  = stripslashes( $raw );
	$data = json_decode( $raw, true );

	return is_array( $data ) ? $data : null;
}

/**
 * Return the first numeric value among the given keys, or null if none qualify.
 *
 * @param array $data Decoded cookie payload.
 * @param array $keys Keys to check, in priority order.
 *
 * @return int|null
 */
function smplfy_gpnf_first_numeric( array $data, array $keys ) {
	foreach ( $keys as $key ) {
		if ( isset( $data[ $key ] ) && is_numeric( $data[ $key ] ) ) {
			return (int) $data[ $key ];
		}
	}

	return null;
}

/**
 * Expire a cookie in the browser by re-issuing it with a past expiry.
 *
 * Sent against both the configured cookie path and the site root (`/`) — and with and
 * without an explicit domain — so we reliably clear the cookie regardless of the path
 * GP Nested Forms originally used. PHP does not expose the original path/domain via
 * `$_COOKIE`, hence the belt-and-braces approach.
 *
 * @param string $name Cookie name.
 *
 * @return void
 */
function smplfy_gpnf_expire_cookie( $name ) {
	$past = time() - DAY_IN_SECONDS;

	$paths = array( '/' );
	if ( defined( 'COOKIEPATH' ) && COOKIEPATH && '/' !== COOKIEPATH ) {
		$paths[] = COOKIEPATH;
	}

	$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';

	foreach ( array_unique( $paths ) as $path ) {
		// With the configured cookie domain (covers subdomain-scoped cookies)...
		if ( $domain ) {
			setcookie( $name, '', $past, $path, $domain );
		}
		// ...and host-only as a fallback.
		setcookie( $name, '', $past, $path );
	}
}

add_action( 'init', 'SmplfyCore\smplfy_gpnf_prune_stale_session_cookies', 1 );
