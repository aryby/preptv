<?php

namespace Database\Factories;

use App\Domain\Catalog\Enums\BillingPeriodUnit;
use App\Models\Currency;
use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanPrice>
 */
class PlanPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'currency_code' => Currency::factory(),
            'billing_period_unit' => BillingPeriodUnit::Month,
            'billing_period_count' => 1,
            'amount_minor' => 1999,
            'effective_from' => now()->subDay(),
            'effective_until' => null,
            'archived_at' => null,
        ];
    }
}
