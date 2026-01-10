<?php

namespace App\Core\Events\System;

use App\Core\Events\BaseEvent;

class SystemJobFailed extends BaseEvent
{
    public function __construct(
        public string $jobClass,
        public string $exceptionMessage,
        public array $jobPayload = []
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'system.job_failed';
    }

    public function getPayload(): array
    {
        return [
            'job_class' => $this->jobClass,
            'exception_message' => $this->exceptionMessage,
            'job_payload' => $this->jobPayload,
        ];
    }
    public function getEntityType(): string
    {
        return 'job';
    }

    public function getEntityId(): int|string|null
    {
        // Use job_id if available, though constructor arg is array jobPayload
        // Wait, constructor does NOT have job_id string, it has jobPayload array?
        // Ah, SystemJobFailed has (string jobClass, string exceptionMessage, array jobPayload)
        // But I am supposed to fix it in AppServiceProvider to pass job_id in payload.
        // Let's assume jobPayload['job_id'] exists if I fixed the instantiation.
        return $this->jobPayload['job_id'] ?? null;
    }
}
