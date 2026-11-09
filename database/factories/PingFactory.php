<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Ping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ping>
 */
class PingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'battery_percent' => fake()->numberBetween(0, 100),
        ];
    }
}
