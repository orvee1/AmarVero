<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->company().' Runner');
        $regularPrice = fake()->numberBetween(2400, 8500);

        return [
            'brand_id' => Brand::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'base_sku' => 'AV-'.fake()->unique()->bothify('??###'),
            'short_description' => fake()->sentence(12),
            'description' => fake()->paragraph(3),
            'status' => ProductStatus::Published,
            'gender' => fake()->randomElement(['men', 'women', 'kids', 'unisex']),
            'material' => fake()->randomElement(['Leather', 'Knit mesh', 'Canvas', 'Synthetic']),
            'care_instructions' => fake()->sentence(10),
            'regular_price' => $regularPrice,
            'sale_price' => fake()->boolean(35) ? $regularPrice - fake()->numberBetween(200, 900) : null,
            'cost_price' => fake()->numberBetween(1200, max(1300, $regularPrice - 900)),
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => now()->addWeeks(2),
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'is_featured' => fake()->boolean(35),
            'is_new_arrival' => fake()->boolean(35),
            'is_best_seller' => fake()->boolean(25),
            'track_inventory' => true,
            'allow_backorder' => false,
            'seo_title' => $name,
            'seo_description' => fake()->sentence(14),
        ];
    }
}
