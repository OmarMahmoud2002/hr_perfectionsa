<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'ac_no'         => $this->faker->unique()->numerify('EMP###'),
            'name'          => $this->faker->name(),
            'basic_salary'  => $this->faker->randomFloat(2, 3000, 15000),
            'is_active'     => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
