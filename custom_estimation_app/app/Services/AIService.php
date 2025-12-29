<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * Generate a product description using OpenAI.
     *
     * @param string $productName
     * @param array $attributes
     * @return string
     */
    public function generateDescription(string $productName, array $attributes = []): string
    {
        // Construct the prompt
        $prompt = "Write a professional, sales-oriented product description for a construction/renovation product named '{$productName}'.";

        if (!empty($attributes)) {
            $attrString = implode(', ', array_map(
                fn($k, $v) => "{$k}: {$v}",
                array_keys($attributes),
                $attributes
            ));
            $prompt .= " The product has the following attributes: {$attrString}.";
        }

        $prompt .= " Keep the description concise (under 100 words) and suitable for a client estimate. Do not include markdown headers.";

        try {
            $result = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that writes professional product descriptions for construction estimates.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 150,
            ]);

            Log::info('OpenAI Response Type: ' . gettype($result));
            if (is_array($result)) {
                Log::info('OpenAI Response Array: ' . json_encode($result));
                return trim($result['choices'][0]['message']['content'] ?? '');
            } elseif (is_object($result)) {
                // Log::info('OpenAI Response Object: ' . serialize($result));
                // Attempt object access, fallback to array if it implements ArrayAccess and object access fails logic?
                // But let's just Stick to the original code for the object path but add logging if it fails.
            }

            return trim($result->choices[0]->message->content);
        } catch (\Exception $e) {
            Log::error('OpenAI Generation Error: ' . $e->getMessage());
            Log::error('OpenAI Generation Trace: ' . $e->getTraceAsString()); // Capture trace to see WHERE the error is
            return "Error generating description. Please try again or enter manually.";
        }
    }
}
