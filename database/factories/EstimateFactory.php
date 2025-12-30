<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Estimate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstimateFactory extends Factory
{
    protected $model = Estimate::class;

    public function definition()
    {
        return [
            'estimate_number' => 'EST-'.$this->faker->unique()->numberBetween(1000, 9999),
            'title' => 'Test Estimate',
            'client_id' => Client::factory(),
            'estimate_date' => now(),
            'expiry_date' => now()->addDays(7),
            'currency' => 'USD',
            'status' => 'draft',
            'type' => 'standard',
            'subtotal' => 1000,
            'total_tax' => 100,
            'discount_total' => 0,
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'grand_total' => 1100,
        ];
    }
}
