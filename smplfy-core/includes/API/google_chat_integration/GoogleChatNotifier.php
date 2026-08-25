<?php

namespace SmplfyCore;

/**
 * Generic Google Chat notifier. Use directly for ad-hoc notifications,
 * or extend it for a specific event source.
 */
class GoogleChatNotifier
{
    // Override in a subclass to point at a different wp-config / env key
    protected const WEBHOOK_CONFIG_KEY = 'GOOGLE_CHAT_WEBHOOK_URL';

    // Seconds to wait for Google Chat before giving up
    protected const REQUEST_TIMEOUT = 10;

    protected string $chatWebhookUrl;

    public function __construct(?string $webhookUrl = null, ?string $configKey = null)
    {
        $this->chatWebhookUrl = $webhookUrl ?? static::resolveWebhookUrl($configKey ?? static::WEBHOOK_CONFIG_KEY);
    }

    public function isConfigured(): bool
    {
        return $this->chatWebhookUrl !== '';
    }

    // Send a plain text message to the Chat space
    public function send(string $messageText): bool
    {
        if (!$this->isConfigured()) {
            SMPLFY_Log::error(
                'Google Chat notification not sent: no webhook URL configured.',
                ['config_key' => static::WEBHOOK_CONFIG_KEY]
            );

            return false;
        }

        $args = [
            'timeout'     => static::REQUEST_TIMEOUT,
            'redirection' => 5,
            'blocking'    => true,
            'headers'     => ['content-type' => 'application/json; charset=UTF-8'],
            'cookies'     => [],
        ];

        $response = WpHttpAPIHelper::send_remote_post($this->chatWebhookUrl, ['text' => $messageText], false, $args);

        if (is_wp_error($response)) {
            SMPLFY_Log::error(
                'Google Chat notification failed: the request did not complete.',
                ['error' => $response->get_error_message()]
            );

            return false;
        }

        $responseCode = (int) wp_remote_retrieve_response_code($response);

        if ($responseCode < 200 || $responseCode >= 300) {
            // Chat's body explains which failure this is, e.g. a revoked
            // webhook or a deleted space
            SMPLFY_Log::error(
                'Google Chat rejected the notification.',
                [
                    'response_code' => $responseCode,
                    'response_body' => wp_remote_retrieve_body($response),
                ]
            );

            return false;
        }

        return true;
    }

    // Read the webhook URL from wp-config, then the environment
    protected static function resolveWebhookUrl(string $configKey): string
    {
        if (defined($configKey) && constant($configKey) !== '') {
            return (string) constant($configKey);
        }

        if (!empty($_ENV[$configKey])) {
            return (string) $_ENV[$configKey];
        }

        $fromEnv = getenv($configKey);

        return $fromEnv !== false ? $fromEnv : '';
    }

    // Chat has no escape syntax, so strip the characters it reads as markup
    // before interpolating anything a third party controls
    protected function sanitizeField($value, int $maxLength = 200): string
    {
        $value = is_scalar($value) ? (string) $value : '';

        // Drop invalid UTF-8 so the preg_* calls below cannot fail
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

        // Flatten control characters so a field cannot fake additional lines
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';

        // < > and | would let a value forge a link, the rest are format markers
        $value = str_replace(['<', '>', '|', '*', '_', '~', '`'], '', $value);

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if (mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength - 1) . '…';
        }

        return $value;
    }

    // Only emit a link for a URL that cannot break out of the link markup
    protected function sanitizeUrl($url): string
    {
        $url = is_scalar($url) ? trim((string) $url) : '';

        if ($url === '' || strpbrk($url, "<>|\"' \t\r\n") !== false) {
            return '';
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : '';
    }
}
