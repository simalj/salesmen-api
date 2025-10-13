<?php

namespace Database\Factories;

use App\Models\Salesman;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Salesman>
 */
class SalesmanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'prosight_id' => $this->faker->unique()->numerify('#####'), // 5 digit number
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'gender_code' => $this->faker->randomElement(['m', 'f']),
            'marital_status_code' => $this->faker->optional()->randomElement(['single', 'married', 'divorced', 'widowed']),
            'titles_before' => $this->faker->optional()->randomElements(['Mgr.', 'Ing.', 'PhDr.', 'MUDr.'], rand(0, 2)),
            'titles_after' => $this->faker->optional()->randomElements(['PhD.', 'CSc.'], rand(0, 1)),
        ];
    }

    /**
     * Indicate that the salesman is male.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender_code' => 'm',
        ]);
    }

    /**
     * Indicate that the salesman is female.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender_code' => 'f',
        ]);
    }

    /**
     * Indicate that the salesman is married.
     */
    public function married(): static
    {
        return $this->state(fn (array $attributes) => [
            'marital_status_code' => 'married',
        ]);
    }
}
