<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            // categories.name はユニーク制約があるので unique() で重複を防ぐ
            'name' => fake()->unique()->word(),
        ];
    }
}
