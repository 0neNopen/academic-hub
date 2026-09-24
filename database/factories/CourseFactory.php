<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->bothify('??-###')),
            'lecturer_name' => fake()->name(),
            'day_of_week' => fake()->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'color' => '#3b82f6',
        ];
    }
}
