<?php

namespace App\Core\Events;

interface EventDispatcherInterface
{
    /**
     * Dispatch an event.
     *
     * @param object $event
     * @return void
     */
    public function dispatch(object $event): void;
}
