<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PdfTemplate>
 */
class PdfTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'html_content' => '<h1>Test</h1>',
            'css_content' => 'body { color: black; }',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'primary_color' => '#333333',
            'secondary_color' => '#555555',
            'font_family' => 'Helvetica',
            'is_active' => true,
            'is_default' => false,
        ];
    }
}
