<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->company().' Category');

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->sentence(12),
            'is_active' => true,
            'is_featured' => fake()->boolean(30),
            'sort_order' => fake()->numberBetween(0, 50),
            'seo_title' => $name,
            'seo_description' => fake()->sentence(14),
        ];
    }
}
