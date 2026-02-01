<?php

namespace App\Webhooks;

use App\Webhooks\Definitions\WebhookEventDefinitionInterface;
use Illuminate\Support\Collection;

class WebhookEventRegistry
{
    protected array $definitions = [];

    /**
     * Register a new event definition.
     */
    public function register(WebhookEventDefinitionInterface $definition): void
    {
        $this->definitions[$definition->name()] = $definition;
    }

    /**
     * Get a definition by name.
     */
    public function get(string $name): ?WebhookEventDefinitionInterface
    {
        return $this->definitions[$name] ?? null;
    }

    /**
     * Get all registered definitions.
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * Get all event names (for dropdowns/validation).
     */
    public function getEventNames(): array
    {
        return array_keys($this->definitions);
    }

    /**
     * Get grouping for UI (e.g. 'Estimate' => ['estimate.created', ...])
     */
    public function getGroupedEvents(): array
    {
        $groups = [];
        foreach ($this->definitions as $def) {
            $parts = explode('.', $def->name());
            $resource = ucfirst($parts[0]);
            $groups[$resource][] = [
                'name' => $def->name(),
                'description' => $def->description(),
            ];
        }
        return $groups;
    }
}
