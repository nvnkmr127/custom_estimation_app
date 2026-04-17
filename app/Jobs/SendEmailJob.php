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
    public $emailLogId;

    /**
     * Create a new job instance.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $attachments
     * @param string|null $emailLogId
     */
    public function __construct(string $to, string $subject, string $body, array $attachments = [], ?string $emailLogId = null)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
        $this->emailLogId = $emailLogId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $gateway = app(MailGatewayInterface::class);

        $sent = $gateway->send($this->to, $this->subject, $this->body, $this->attachments);

        if ($this->emailLogId) {
            \App\Models\EmailLog::where('id', $this->emailLogId)->update([
                'status' => $sent ? 'sent' : 'failed',
                'error_message' => $sent ? null : 'Gateway declined to send',
            ]);
        }

        if (!$sent) {
            $attempt = $this->attempts();
            Log::error("SendEmailJob Failed (Attempt $attempt): Gateway declined to send", [
                'to' => $this->to,
                'subject' => $this->subject
            ]);
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
