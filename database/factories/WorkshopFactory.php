<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workshop>
 */
class WorkshopFactory extends Factory
{
    protected $model = Workshop::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = Carbon::instance(fake()->dateTimeBetween('+1 day', '+2 months'));

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional(0.7)->paragraph(),
            'workshop_category_id' => WorkshopCategory::factory(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addHours(fake()->numberBetween(2, 16)),
            'capacity' => fake()->numberBetween(5, 40),
            'created_by' => User::factory(),
        ];
    }

    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $startsAt = Carbon::instance(fake()->dateTimeBetween('+1 day', '+1 month'));

            return [
                'starts_at' => $startsAt,
                'ends_at' => (clone $startsAt)->addHours(fake()->numberBetween(3, 12)),
            ];
        });
    }
}
