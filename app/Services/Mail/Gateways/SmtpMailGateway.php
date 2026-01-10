<?php

namespace App\Services\Mail\Gateways;

use App\Services\Mail\Contracts\MailGatewayInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class SmtpMailGateway implements MailGatewayInterface
{
    /**
     * Send an email via SMTP (using Laravel's Mail facade).
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $attachments
     * @return bool
     */
    public function send(string $to, string $subject, string $body, array $attachments = []): bool
    {
        try {
            Mail::html($body, function ($message) use ($to, $subject, $attachments) {
                $message->to($to)
                    ->subject($subject);

                foreach ($attachments as $path => $name) {
                    $message->attach($path, ['as' => $name]);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('SMTP Mail Gateway Error: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'exception' => $e
            ]);
            return false;
        }
    }
}
