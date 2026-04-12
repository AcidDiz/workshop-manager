<?php

namespace Database\Factories;

use App\Models\WorkshopCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkshopCategory>
 */
class WorkshopCategoryFactory extends Factory
{
    protected $model = WorkshopCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name.'-'.fake()->unique()->numerify('###')),
        ];
    }
}
