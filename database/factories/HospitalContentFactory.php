<?php

namespace Database\Factories;

use App\Models\Hospital\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hospital\HospitalContent>
 */
class HospitalContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'address' => $this->faker->address,
        ];
    }
}
