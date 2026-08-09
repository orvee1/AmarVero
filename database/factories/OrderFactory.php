<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(2400, 9000);
        $shipping = fake()->numberBetween(80, 180);

        return [
            'user_id' => User::factory(),
            'order_number' => 'AV-'.now()->format('ymd').'-'.fake()->unique()->numberBetween(10000, 99999),
            'customer_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+88017'.fake()->numerify('########'),
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
            'currency_code' => 'BDT',
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => 0,
            'shipping_total' => $shipping,
            'grand_total' => $subtotal + $shipping,
            'placed_at' => now()->subDays(fake()->numberBetween(0, 14)),
        ];
    }
}
