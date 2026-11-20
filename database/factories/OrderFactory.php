<?php

namespace Database\Factories;

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Currency;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        return [
            'public_id' => (string) Str::ulid(),
            'currency_code' => Currency::factory(),
            'status' => OrderStatus::PendingPayment,
            'subtotal_minor' => 1999,
            'tax_minor' => 0,
            'total_minor' => 1999,
            'customer_snapshot' => [
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
            ],
            'seller_snapshot' => null,
        ];
    }
}
