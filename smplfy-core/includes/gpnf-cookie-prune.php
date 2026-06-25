<?php
/**
 * Shorten GP Nested Forms session lifetime to stop stale cookies accumulating.
 *
 * GP Nested Forms sets one `gpnf_form_session_*` cookie per form/endpoint. By default
 * each is given a one-week browser lifetime (`WEEK_IN_SECONDS`). On sites with many
 * nested-form pages these pile up until the combined Cookie request header exceeds the
 * web server's limit (Apache `LimitRequestFieldSize`, ~8 KB), producing intermittent
 * `400 Bad Request` errors on navigation — and WordPress starts ignoring oversized
 * `Set-Cookie` responses ("combined size ... must be <= 4096 characters").
 *
 * GP Nested Forms derives both the session cookie's expiry and its orphaned-entry
 * cleanup window from the `gpnf_expiration_modifier` filter (default `WEEK_IN_SECONDS`,
 * matching the 7-day cookie we observed). Lowering it means:
 *
 *   - New session cookies get a short browser expiry, so the browser auto-expires them
 *     once a form has been idle past the TTL. Active sessions are unaffected because GP
 *     Nested Forms re-issues (and so refreshes the expiry of) the cookie on every render.
 *   - Orphaned child entries (added but whose parent form was never submitted) are
 *     cleaned from the database sooner. With ~10-minute completion times and no
 *     Save & Continue on these forms, legitimate entries are always attached to their
 *     parent well within the window, so only genuinely abandoned ones are removed.
 *
 * Tune the window with the `SMPLFY_GPNF_SESSION_TTL` constant (seconds). The fix lives
 * entirely in smplfy-core via GP Nested Forms' own filter; no GP Nested Forms files are
 * touched.
 *
 * @package SMP Core
 */

namespace SmplfyCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cap the GP Nested Forms session lifetime (cookie expiry + orphaned-entry window).
 *
 * @return int Lifetime in seconds.
 */
function smplfy_gpnf_session_ttl() {
	$ttl = defined( 'SMPLFY_GPNF_SESSION_TTL' ) ? (int) SMPLFY_GPNF_SESSION_TTL : HOUR_IN_SECONDS;

	// Guard against a misconfigured/zero override silently disabling sessions entirely.
	if ( $ttl < 1 ) {
		$ttl = HOUR_IN_SECONDS;
	}

	return $ttl;
}

add_filter( 'gpnf_expiration_modifier', 'SmplfyCore\smplfy_gpnf_session_ttl' );
