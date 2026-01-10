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
     * @return void
     */
    public function dispatch(string $to, string $subject, ?string $view = null, array $data = [], ?string $rawBody = null): void
    {
        // 1. Render the body
        if ($view) {
            $body = $this->templateService->render($view, $data);
        } else {
            $body = $this->templateService->renderString($rawBody ?? '', $data);
        }

        // 2. Dispatch job to queue
        SendEmailJob::dispatch($to, $subject, $body);
    }
}
