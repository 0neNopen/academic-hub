<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'deadline' => now()->addDays(2),
            'submission_url' => fake()->url(),
            'status' => 'pending',
            'reminder_h24_sent_at' => null,
            'reminder_h3_sent_at' => null,
        ];
    }
}
