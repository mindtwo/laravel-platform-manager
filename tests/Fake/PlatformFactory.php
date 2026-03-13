<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake;

use Illuminate\Database\Eloquent\Factories\Factory;
use mindtwo\LaravelPlatformManager\Models\Platform;

/**
 * @extends Factory<Platform>
 */
class PlatformFactory extends Factory
{
    protected $model = Platform::class;

    public function definition(): array
    {
        return [
            'is_active' => true,
            'hostname' => $this->faker->domainName,
            'settings' => null,
        ];
    }

    public function local(): static
    {
        return $this->state(['hostname' => 'localhost']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }
}
