<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake;

use Illuminate\Database\Eloquent\Factories\Factory;
use mindtwo\LaravelPlatformManager\Models\Platform;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Platform>
 */
class PlatformFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Platform::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'visibility' => true,
            'name' => $this->faker->words(rand(1, 3), true),
            'hostname' => $this->faker->domainName,
        ];
    }

    /**
     * Indicate that the platform is hidden.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function main()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_main' => 1,
            ];
        });
    }

    /**
     * Indicate that the platform is hidden.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function hidden()
    {
        return $this->state(function (array $attributes) {
            return [
                'visibility' => 0,
            ];
        });
    }
}
