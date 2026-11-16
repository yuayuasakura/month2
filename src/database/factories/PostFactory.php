<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;
use App\Models\User;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'   => fake()->sentence(),
            'content' => fake()->paragraph(),
            'user_id' => User::factory(),
            // posts.category_id は必須（NOT NULL）なので、カテゴリーも一緒に作る
            'category_id' => Category::factory(),
        ];
    }
}
