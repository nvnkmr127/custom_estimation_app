<?php

namespace App\Infrastructure\Webhooks;

use App\Infrastructure\Webhooks\Definitions\WebhookEventDefinitionInterface;
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
            $resource = $this->resolveGroupName($def);
            $groups[$resource][] = [
                'name' => $def->name(),
                'description' => $def->description(),
                'samplePayload' => $def->samplePayload(),
            ];
        }

        // Sort groups alphabetically
        ksort($groups);

        return $groups;
    }

    protected function resolveGroupName(WebhookEventDefinitionInterface $def): string
    {
        $class = $def->resourceClass();
        if (empty($class)) {
            $parts = explode('.', $def->name());
            return ucfirst($parts[0]);
        }

        $shortName = (new \ReflectionClass($class))->getShortName();

        // Map common models to plural categories
        return match ($shortName) {
            'Estimate' => 'Estimates Management',
            'Client' => 'Client Relations',
            'User' => 'Identity & Access',
            'EstimateComment' => 'Collaboration',
            'EstimateApproval' => 'Approvals & Workflows',
            default => $shortName . ' Events'
        };
    }
}
