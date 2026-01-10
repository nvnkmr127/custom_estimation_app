<?php

namespace App\Listeners;

use App\Core\Events\DomainEvent;
use App\Services\Automation\AutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AutomationListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $automationService;

    public function __construct(AutomationService $automationService)
    {
        $this->automationService = $automationService;
    }

    /**
     * Handle the event.
     */
    public function handle(DomainEvent $event): void
    {
        $this->automationService->evaluate($event);
    }
}
