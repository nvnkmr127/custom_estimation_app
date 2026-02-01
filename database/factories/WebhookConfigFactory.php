<?php

namespace Database\Factories;

use App\Models\WebhookConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookConfigFactory extends Factory
{
    protected $model = WebhookConfig::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word,
            'url' => $this->faker->url,
            'secret' => $this->faker->sha256,
            'events' => ['*'],
            'headers' => [],
            'status' => 'active',
            'concurrency_limit' => 5,
            'http_method' => 'POST',
            'rate_limit' => 60,
        ];
    }
}
