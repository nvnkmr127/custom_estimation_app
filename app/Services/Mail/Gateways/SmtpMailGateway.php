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
            // override config from DB settings if available
            $host = \App\Models\Setting::getCached('smtp_host');
            if ($host) {
                config([
                    'mail.mailers.smtp.host' => $host,
                    'mail.mailers.smtp.port' => \App\Models\Setting::getCached('smtp_port', 587),
                    'mail.mailers.smtp.encryption' => \App\Models\Setting::getCached('smtp_encryption', 'tls'),
                    'mail.mailers.smtp.username' => \App\Models\Setting::getCached('smtp_username'),
                    'mail.mailers.smtp.password' => \App\Models\Setting::getCached('smtp_password'),
                    'mail.from.address' => \App\Models\Setting::getCached('smtp_from_address', config('mail.from.address')),
                    'mail.from.name' => \App\Models\Setting::getCached('smtp_from_name', config('mail.from.name')),
                ]);

                // Purge the 'smtp' driver to ensure a fresh instance with new config is created
                Mail::purge('smtp');
            }

            // Always use the 'smtp' mailer specifically, so we don't accidentally use 'log'
            Mail::mailer('smtp')->send([], [], function ($message) use ($to, $subject, $body, $attachments) {
                $message->to($to)
                    ->subject($subject)
                    ->html($body);

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
