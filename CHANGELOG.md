# Changelog

## 1.1.0

### Added

- **`GoogleChatNotifier`** (`includes/API/google_chat_integration`) — sends plain
  text to a Google Chat space. Resolves the webhook URL from a wp-config
  constant, then `$_ENV`, then `getenv()`, through a `$configKey` constructor
  argument so each site can point at its own.

  It also carries the sanitizers. Chat has no escape syntax for its formatting
  characters, so `sanitizeField()` strips what Chat reads as markup and flattens
  control characters, and `sanitizeUrl()` emits a link only for an http/https URL
  that cannot break out of the link markup. Without these, a value controlled by
  a third party can forge a second, convincing link in a message.

- `includes/API` is now registered with `SMPLFY_Require`. It was never in the
  list in `smplfy-core.php`, so anything placed there did not exist at runtime.

### Changed

- **`WpHttpAPIHelper::send_remote_post()` returns the full `wp_remote_post()`
  result** rather than `$response['response']`, which was array access on a
  `WP_Error` whenever a request did not complete. Callers now use
  `is_wp_error()`, `wp_remote_retrieve_response_code()` and
  `wp_remote_retrieve_body()`. **This changes the return shape.** The method had
  no callers when it changed.

- `GoogleChatNotifier::sanitizeField()` and `sanitizeUrl()` are `public` rather
  than `protected`, so callers that build their own message text get the same
  escaping guarantees.

### Removed

- **`GoogleChatWebhookHandler`.** This abstract base made core own the control
  flow and declare its extension points as `shouldNotify()` and
  `buildMessage()`, which meant a site needing to handle more event types could
  only do so by changing core — a plugin that installs on every client site.

  Anything subclassing it should hold a `GoogleChatNotifier` instead and move
  its `shouldNotify()`/`buildMessage()` bodies into its own handler:

  ```php
  $this->chat = new GoogleChatNotifier( null, 'SITE_WEBHOOK_CONFIG_KEY' );
  // ...
  if ( $this->chat->send( $message ) ) { /* delivered */ }
  ```

  The class was on `main` for under a day and had one known subclass, which was
  migrated alongside its removal.

### Fixed

- `Exception` in `smplfy-core.php` and `SMPLFY_Require` resolved to
  `SmplfyCore\Exception`, a class that does not exist, so the `try`/`catch`
  around directory loading could never catch and a bad path produced an uncaught
  `Error` instead of the intended `error_log()`. Now `\Exception`.

- The test suite referenced core's classes unqualified after they moved under
  the `SmplfyCore` namespace, which left mocks unable to satisfy type hints and
  `SMPLFY_Require` unresolvable. Adding the missing imports took the suite from
  27 failures to 4.

## 1.0.1

No changelog was kept before 1.1.0.
