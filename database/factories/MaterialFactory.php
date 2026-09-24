<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(3),
            'meeting_number' => fake()->numberBetween(1, 14),
            'file_path' => null,
            'external_link' => fake()->url(),
            'notes' => fake()->sentence(),
        ];
    }
}
