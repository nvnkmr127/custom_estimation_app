<?php

namespace App\Infrastructure\Webhooks;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PayloadValidator
{
    /**
     * Validate the payload against the provided schema (Laravel Validation Rules).
     *
     * @param array $payload
     * @param array $rules (The 'schema')
     * @throws ValidationException
     */
    public function validate(array $payload, array $rules): void
    {
        // Simple case: $rules is the rules array directly
        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
