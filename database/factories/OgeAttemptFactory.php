<?php

namespace Database\Factories;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OgeAttemptFactory extends Factory
{
    protected $model = OgeAttempt::class;

    public function definition(): array
    {
        return [
            'variant_id' => OgeVariant::factory(),
            'student_id' => User::factory(),
            'status'     => 'active',
            'started_at' => now(),
        ];
    }
}
