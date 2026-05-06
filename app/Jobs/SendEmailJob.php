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

        try {
            $gateway->send($this->to, $this->subject, $this->body, $this->attachments);

            if ($this->emailLogId) {
                \App\Models\EmailLog::where('id', $this->emailLogId)->update([
                    'status' => 'sent',
                    'error_message' => null,
                ]);
            }
        } catch (\Exception $e) {
            // Log the specific attempt failure but re-throw for queue retry
            $attempt = $this->attempts();
            Log::warning("SendEmailJob Attempt $attempt Failed: " . $e->getMessage(), [
                'to' => $this->to,
                'subject' => $this->subject
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        if ($this->emailLogId) {
            \App\Models\EmailLog::where('id', $this->emailLogId)->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
        
        Log::error("SendEmailJob Permanently Failed: " . $exception->getMessage(), [
            'to' => $this->to,
            'subject' => $this->subject,
            'log_id' => $this->emailLogId
        ]);
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
