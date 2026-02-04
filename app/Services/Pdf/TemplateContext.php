<?php

namespace App\Services\Pdf;

class TemplateContext
{
    protected $variables = [];

    public function __construct(array $variables = [])
    {
        $this->variables = $variables;
    }

    public function get(string $key, $default = null)
    {
        return $this->variables[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->variables;
    }

    public function has(string $key): bool
    {
        return isset($this->variables[$key]);
    }

    public function merge(array $newVariables): self
    {
        $this->variables = array_merge($this->variables, $newVariables);
        return $this;
    }
}
