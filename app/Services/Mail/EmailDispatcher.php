<?php

namespace App\Services\Mail;

use App\Jobs\SendEmailJob;
use App\Services\Templates\TemplateService;

class EmailDispatcher
{
    protected $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Dispatch an email to the queue.
     *
     * @param string $to
     * @param string $subject
     * @param string|null $view Template view name (optional)
     * @param array $data Data for the template
     * @param string|null $rawBody Raw HTML body (optional, used if view is null)
     * @param string $priority Priority level (critical, high, normal, low)
     * @return void
     */
    public function dispatch(string $to, string $subject, ?string $view = null, array $data = [], ?string $rawBody = null, string $priority = 'normal'): void
    {
        // 0. Priority Rule: High events trigger instant in-app (if we have a user)
        if ($priority === \App\Core\Events\DomainEvent::PRIORITY_HIGH) {
            $user = \App\Models\User::where('email', $to)->first();
            if ($user) {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'in_app_alert',
                    'notifiable_type' => get_class($user),
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'message' => $subject,
                        'urgency' => $priority,
                    ]),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 1. Render the body
        if ($view) {
            $body = $this->templateService->render($view, $data, $priority);
        } else {
            $body = $this->templateService->renderString($rawBody ?? '', array_merge($data, $this->templateService->getContextVariables($priority, $data)));
        }

        // 2. Create Email Log for Tracking
        try {
            $log = \App\Models\EmailLog::create([
                'recipient_email' => $to,
                'subject' => $subject,
                'status' => 'queued',
                'event_type' => data_get($data, 'event_type'),
                'entity_type' => data_get($data, 'entity_type'),
                'entity_id' => data_get($data, 'entity_id'),
            ]);

            // 3. Inject Tracking Pixel
            $trackingPixel = '<img src="' . route('tracking.open', $log->id) . '" width="1" height="1" style="display:none;" alt="" />';

            // Check if body has </body> tag to insert before, otherwise append
            if (strpos($body, '</body>') !== false) {
                $body = str_replace('</body>', $trackingPixel . '</body>', $body);
            } else {
                $body .= $trackingPixel;
            }

            // 4. Rewrite Links for Click Tracking
            // Regex to find href="..."
            $body = preg_replace_callback('/href="([^"]+)"/i', function ($matches) use ($log) {
                $originalUrl = $matches[1];

                // Skip mailto:, tel:, #, javascript: links
                if (preg_match('/^(mailto:|tel:|#|javascript:)/i', $originalUrl)) {
                    return $matches[0];
                }

                // Construct tracking URL
                $trackingUrl = route('tracking.click', ['id' => $log->id, 'target' => $originalUrl]);

                return 'href="' . $trackingUrl . '"';
            }, $body);

        } catch (\Exception $e) {
            // Log error but continue sending email without tracking if simple logging fails
            \Illuminate\Support\Facades\Log::error('Email Tracking Setup Failed: ' . $e->getMessage());
        }

        \Illuminate\Support\Facades\Bus::dispatch(new SendEmailJob($to, $subject, $body, [], $log->id ?? null));
    }
}
