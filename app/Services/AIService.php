<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AIService
{
    /**
     * Generate a product description using OpenAI.
     */
    public function generateDescription(string $productName, array $attributes = [])
    {
        // Security: Sanitize input to prevent prompt injection
        $sanitizedName = strip_tags(substr($productName, 0, 100));
        
        // Construct the prompt with clear delimiters
        $prompt = "Write a professional, sales-oriented product description for a construction/renovation product.\n";
        $prompt .= "Product Name: '''{$sanitizedName}'''\n";

        if (! empty($attributes)) {
            $attrString = implode(', ', array_map(
                fn ($k, $v) => strip_tags("{$k}: {$v}"),
                array_keys($attributes),
                $attributes
            ));
            $prompt .= "Attributes: {$attrString}\n";
        }

        $prompt .= "\nInstructions: Keep the description concise (under 100 words) and suitable for a client estimate. Do not include markdown headers or internal metadata.";

        try {
            // Performance: Simple cache to avoid redundant AI costs
            $cacheKey = 'ai_desc_' . md5($sanitizedName . serialize($attributes));
            if ($cached = \Illuminate\Support\Facades\Cache::get($cacheKey)) {
                return $cached;
            }

            $result = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a professional copywriter specialized in construction and home improvement estimation.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 150,
            ]);

            $content = '';
            if (is_array($result)) {
                $content = $result['choices'][0]['message']['content'] ?? '';
            } elseif (is_object($result)) {
                $content = $result->choices[0]->message->content ?? '';
            }

            $finalDesc = trim($content);
            if ($finalDesc) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, $finalDesc, 86400); // 24h cache
            }
            return $finalDesc ?: 'Product description pending.';

        } catch (\Exception $e) {
            Log::error('OpenAI Generation Error: '.$e->getMessage());
            Log::error('OpenAI Generation Trace: '.$e->getTraceAsString()); // Capture trace to see WHERE the error is

            return 'Error generating description. Please try again or enter manually.';
        }
    }
}
