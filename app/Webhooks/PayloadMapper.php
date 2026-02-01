<?php

namespace App\Webhooks;

use Illuminate\Support\Arr;
use Carbon\Carbon;

class PayloadMapper
{
    /**
     * Map the source payload to a new structure based on the mapping config.
     *
     * @param array $source The original payload (usually flattened or nested).
     * @param array $mapping The mapping configuration.
     * @return array The transformed payload.
     */
    public function map(array $source, array $mapping): array
    {
        $result = [];

        foreach ($mapping as $targetKey => $rule) {
            $value = $this->resolveValue($source, $rule);

            // Support nested target keys using dot notation
            if (str_contains($targetKey, '.')) {
                Arr::set($result, $targetKey, $value);
            } else {
                $result[$targetKey] = $value;
            }
        }

        return $result;
    }

    protected function resolveValue(array $source, mixed $rule): mixed
    {
        // 1. Simple Rename: "target" => "source.path"
        if (is_string($rule)) {
            return Arr::get($source, $rule);
        }

        // 2. Advanced Rule: "target" => ["source" => "...", "default" => "...", "transform" => "..."]
        if (is_array($rule)) {
            $sourcePath = $rule['source'] ?? null;
            $defaultValue = $rule['default'] ?? null;
            $transform = $rule['transform'] ?? null;
            $staticValue = $rule['value'] ?? null;

            // Fixed Value override
            if (!is_null($staticValue)) {
                return $staticValue;
            }

            // Get value from source
            $value = $sourcePath ? Arr::get($source, $sourcePath) : null;

            // Apply Default if null
            if (is_null($value)) {
                $value = $defaultValue;
            }

            // Apply Transformation
            if ($transform && !is_null($value)) {
                $value = $this->applyTransform($value, $transform);
            }

            return $value;
        }

        return null;
    }

    protected function applyTransform(mixed $value, string $transform): mixed
    {
        $parts = explode(':', $transform, 2);
        $type = $parts[0];
        $param = $parts[1] ?? null;

        return match ($type) {
            'date' => $this->transformDate($value, $param),
            'string' => (string) $value,
            'int' => (int) $value,
            'bool' => (bool) $value,
            'json' => json_encode($value),
            default => $value,
        };
    }

    protected function transformDate(mixed $value, ?string $format): string
    {
        try {
            $date = Carbon::parse($value);
            return $format ? $date->format($format) : $date->toIso8601String();
        } catch (\Exception $e) {
            return (string) $value;
        }
    }
}
