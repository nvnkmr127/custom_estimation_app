<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidPdfContent implements Rule
{
    protected $missingTags = [];

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->missingTags = [];
        $content = (string) $value;

        // 1. Must include Item Loop
        if (
            !preg_match('/\{(?:\s*LOOP_ITEMS\s*|%7BLOOP_ITEMS%7D)\}/si', $content) &&
            !preg_match('/\{(?:\s*LOOP_SECTIONS\s*|%7BLOOP_SECTIONS%7D)\}/si', $content)
        ) {
            $this->missingTags[] = '{LOOP_ITEMS} or {LOOP_SECTIONS}';
        }

        // 2. Must include Total Cost
        if (!preg_match('/\{(?:\s*grand_total\s*|%7Bgrand_total%7D)\}/si', $content)) {
            $this->missingTags[] = '{grand_total}';
        }

        return empty($this->missingTags);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The template is missing critical variables: ' . implode(', ', $this->missingTags) . '. These are required to generate a valid estimate PDF.';
    }
}
