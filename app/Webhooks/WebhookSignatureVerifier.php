<?php

namespace App\Webhooks;

use Exception;

class WebhookSignatureVerifier
{
    /**
     * Verify the signature of a webhook request.
     *
     * @param string $payload The raw JSON payload body.
     * @param string $signatureHeader The X-Webhook-Signature header value.
     * @param string $timestampHeader The X-Webhook-Timestamp header value.
     * @param string $secret The shared secret key.
     * @param int $toleranceSeconds Tolerance for timestamp drift (default 5 mins).
     * @return bool True if valid, throws Exception otherwise.
     * @throws Exception
     */
    public function verify(string $payload, string $signatureHeader, string $timestampHeader, string $secret, int $toleranceSeconds = 300): bool
    {
        if (empty($signatureHeader) || empty($timestampHeader)) {
            throw new Exception("Missing signature or timestamp headers.");
        }

        // 1. Verify Timestamp (Replay Protection)
        $timestamp = (int) $timestampHeader;
        if (abs(time() - $timestamp) > $toleranceSeconds) {
            throw new Exception("Timestamp outside of tolerance window.");
        }

        // 2. Reconstruct Signed String
        $signedString = "{$timestamp}.{$payload}";

        // 3. Calculate Expected Signature
        $expectedSignature = hash_hmac('sha256', $signedString, $secret);

        // 4. Constant Time Comparison
        if (!hash_equals($expectedSignature, $signatureHeader)) {
            throw new Exception("Invalid signature.");
        }

        return true;
    }

    /**
     * Safe checker that returns boolean without throwing.
     */
    public function isValid(string $payload, string $signatureHeader, string $timestampHeader, string $secret): bool
    {
        try {
            return $this->verify($payload, $signatureHeader, $timestampHeader, $secret);
        } catch (Exception $e) {
            return false;
        }
    }
}
