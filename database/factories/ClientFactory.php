<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition()
    {
        return [
            'name' => fake()->company,
            'email' => fake()->safeEmail,
            'address' => fake()->address,
        ];
    }
}
