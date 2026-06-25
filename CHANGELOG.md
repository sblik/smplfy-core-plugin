# Changelog

All notable changes to the Smplfy Core plugin are documented here.

The version recorded here must match the `Version:` header in
`smplfy-core/smplfy-core.php` and the `SMP_CORE_VERSION` constant. WordPress reads that
header to display the active version on the Plugins screen and to compare against an
uploaded zip when replacing the plugin, so bump all three together on every release.

This project adheres to [Semantic Versioning](https://semver.org/): `MAJOR.MINOR.PATCH`.

## [1.1.0] - 2026-06-25

### Added
- `includes/gpnf-cookie-prune.php`: caps the GP Nested Forms session lifetime via the
  `gpnf_expiration_modifier` filter (default 1 hour, overridable with the
  `SMPLFY_GPNF_SESSION_TTL` constant). Stops `gpnf_form_session_*` cookies accumulating
  until the Cookie request header exceeds Apache's `LimitRequestFieldSize` and causes
  intermittent `400 Bad Request` errors.
- `SMP_CORE_VERSION` constant for tracking the active plugin version in code.

## [1.0.1]

### Added
- Initial tracked release: core utilities, entities, repositories, Gravity Forms /
  Gravity Flow integrations, logging, and WP API includes.
