<?php

namespace Database\Factories;

use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'event_type' => 'test.event',
            'idempotency_key' => $this->faker->uuid(),
            'payload' => ['foo' => 'bar'],
            'occurred_at' => now(),
        ];
    }
}
