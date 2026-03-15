<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\ImportBatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        $clockIn  = $this->faker->boolean(80)
            ? Carbon::createFromTime($this->faker->numberBetween(8, 11), $this->faker->numberBetween(0, 59))
            : null;

        $clockOut = $clockIn && $this->faker->boolean(85)
            ? Carbon::createFromTime($this->faker->numberBetween(16, 19), $this->faker->numberBetween(0, 59))
            : null;

        return [
            'employee_id'      => Employee::factory(),
            'import_batch_id'  => ImportBatch::factory(),
            'date'             => $this->faker->dateThisMonth(),
            'clock_in'         => $clockIn?->format('H:i:s'),
            'clock_out'        => $clockOut?->format('H:i:s'),
            'is_absent'        => ($clockIn === null && $clockOut === null),
            'late_minutes'     => 0,
            'overtime_minutes' => 0,
            'work_minutes'     => 0,
            'notes'            => null,
        ];
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_in'         => null,
            'clock_out'        => null,
            'is_absent'        => true,
            'late_minutes'     => 0,
            'overtime_minutes' => 0,
            'work_minutes'     => 0,
        ]);
    }
}
