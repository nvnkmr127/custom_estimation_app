<?php

namespace App\Jobs;

use App\Services\Mail\Contracts\MailGatewayInterface;
use App\Services\Mail\Gateways\SmtpMailGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to;
    public $subject;
    public $body;
    public $attachments;

    /**
     * Create a new job instance.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $attachments
     */
    public function __construct(string $to, string $subject, string $body, array $attachments = [])
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // In a real DI scenario, we might want to resolve the interface from the container.
        // For now, we instantiate SmtpMailGateway or resolve it.
        // To keep it flexible, let's resolve the interface.
        // Ensure AppServiceProvider binds MailGatewayInterface to SmtpMailGateway.

        $gateway = app(MailGatewayInterface::class);

        if (!$gateway->send($this->to, $this->subject, $this->body, $this->attachments)) {
            // Check if we should retry or fail
            // For now, throwing an exception triggers the queue retry mechanism
            throw new \Exception("Failed to send email to {$this->to}");
        }
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [30, 60, 120];
    }
}
