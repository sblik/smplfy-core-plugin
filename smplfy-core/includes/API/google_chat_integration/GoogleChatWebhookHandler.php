<?php

namespace SmplfyCore;

// Loaded explicitly so this file does not depend on the order SMPLFY_Require
// happens to walk the directory in
require_once __DIR__ . '/GoogleChatNotifier.php';

/**
 * Base for "incoming webhook -> Google Chat" flows.
 * Subclasses decide which payloads matter and what the message looks like.
 */
abstract class GoogleChatWebhookHandler extends GoogleChatNotifier
{
    protected array $payloadData = [];

    // Should this payload produce a notification?
    abstract protected function shouldNotify(): bool;

    // Build the Chat message from $this->payloadData
    abstract protected function buildMessage(): string;

    // Capture the incoming JSON body
    public function getIncomingData(): bool
    {
        $rawInput = file_get_contents('php://input');
        $decoded = json_decode($rawInput, true);

        if (!is_array($decoded)) {
            SMPLFY_Log::warn(
                'Google Chat webhook received a body that is not a JSON object.',
                ['json_error' => json_last_error_msg()]
            );
            $this->payloadData = [];

            return false;
        }

        $this->payloadData = $decoded;

        return !empty($this->payloadData);
    }

    // Supply the decoded payload instead of reading php://input
    public function setPayload(array $payload): void
    {
        $this->payloadData = $payload;
    }

    // Process the webhook and return status + body for the caller to emit
    public function handle(): array
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 500,
                'body' => ['status' => 'error', 'message' => 'Missing Webhook URL configuration'],
            ];
        }

        if (!$this->shouldNotify()) {
            // Not a delivery failure, so return 200 to stop the sender retrying
            return ['status' => 200, 'body' => ['status' => 'ignored']];
        }

        if ($this->send($this->buildMessage())) {
            return ['status' => 200, 'body' => ['status' => 'success']];
        }

        // send() has already logged why
        return [
            'status' => 502,
            'body' => ['status' => 'error', 'message' => 'Failed to deliver to Google Chat'],
        ];
    }

    // Process the webhook and write the response directly (outside WordPress)
    public function processWebhook(): void
    {
        $result = $this->handle();

        http_response_code($result['status']);

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }

        echo json_encode($result['body']);
    }
}
