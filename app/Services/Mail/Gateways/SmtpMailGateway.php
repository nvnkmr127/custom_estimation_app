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
        $fromAddress = \App\Models\Setting::getCached('smtp_from_address', config('mail.from.address'));
        $fromName = \App\Models\Setting::getCached('smtp_from_name', config('mail.from.name'));

        $to = trim($to);
        if (empty($to)) {
            Log::error('SMTP Mail Gateway: Recipient address is empty');
            return false;
        }

        if (empty($subject)) {
            $subject = '(No Subject)';
        }

        try {
            // override config from DB settings if available
            $host = \App\Models\Setting::getCached('smtp_host');
            
            if ($host) {
                $port = \App\Models\Setting::getCached('smtp_port', 587);
                $encryption = \App\Models\Setting::getCached('smtp_encryption', 'tls');
                $username = \App\Models\Setting::getCached('smtp_username');
                $password = \App\Models\Setting::getCached('smtp_password');

                config([
                    'mail.mailers.smtp.host' => $host,
                    'mail.mailers.smtp.port' => $port,
                    'mail.mailers.smtp.encryption' => $encryption,
                    'mail.mailers.smtp.username' => $username,
                    'mail.mailers.smtp.password' => $password,
                    'mail.from.address' => $fromAddress,
                    'mail.from.name' => $fromName,
                ]);

                // Purge the 'smtp' driver to ensure a fresh instance with new config is created
                Mail::purge('smtp');
            } else {
                 Log::info('SMTP Gateway: No custom SMTP host configured, using defaults.');
            }

            // Use the 'smtp' mailer specifically
            Mail::mailer('smtp')->send([], [], function ($message) use ($to, $subject, $body, $attachments, $fromAddress, $fromName) {
                $message->to($to);
                
                if ($fromAddress) {
                    $message->from($fromAddress, $fromName);
                }
                
                $message->subject($subject);
                
                if (!empty($body)) {
                    $message->html($body);
                } else {
                    $message->text(' '); // Empty body fallback
                }

                foreach ($attachments as $path => $name) {
                    if (is_file($path)) {
                        $message->attach($path, ['as' => $name]);
                    } else {
                        Log::warning("SMTP Gateway: Attachment not found at $path");
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('SMTP Mail Gateway Error: ' . $e->getMessage(), [
                'to' => $to,
                'from' => $fromAddress ?? 'not-set',
                'subject' => $subject,
                'host' => $host ?? 'default',
                'exception_class' => get_class($e),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ]);
            throw $e;
        }
    }
}
