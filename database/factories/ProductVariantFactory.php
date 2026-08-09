<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'AV-VAR-'.fake()->unique()->bothify('??####'),
            'option_label' => fake()->randomElement(['Black / EU 40', 'Black / EU 41', 'Tan / EU 42', 'White / EU 39']),
            'stock_quantity' => fake()->numberBetween(4, 30),
            'reserved_quantity' => fake()->numberBetween(0, 3),
            'low_stock_threshold' => 3,
            'allow_backorder' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
            'weight_grams' => fake()->numberBetween(500, 1200),
            'dimensions' => [
                'length_cm' => fake()->numberBetween(28, 34),
                'width_cm' => fake()->numberBetween(16, 22),
                'height_cm' => fake()->numberBetween(10, 15),
            ],
        ];
    }
}
