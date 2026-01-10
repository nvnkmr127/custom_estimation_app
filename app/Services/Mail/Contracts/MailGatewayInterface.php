<?php

namespace App\Services\Mail\Contracts;

interface MailGatewayInterface
{
    /**
     * Send an email.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email content (HTML)
     * @param array $attachments Optional array of attachments (path => name)
     * @return bool True if successful, false otherwise
     */
    public function send(string $to, string $subject, string $body, array $attachments = []): bool;
}
