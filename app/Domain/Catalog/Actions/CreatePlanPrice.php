<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\BillingPeriodUnit;
use App\Models\Currency;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreatePlanPrice
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, array $data): PlanPrice
    {
        Gate::forUser($actor)->authorize('create', PlanPrice::class);

        $validated = Validator::make($data, [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'billing_period_unit' => ['required', Rule::enum(BillingPeriodUnit::class)],
            'billing_period_count' => ['required', 'integer', 'min:1', 'max:65535'],
            'amount_minor' => ['required', 'integer', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ])->validate();

        $effectiveFrom = Carbon::parse($validated['effective_from']);
        $effectiveUntil = filled($validated['effective_until'])
            ? Carbon::parse($validated['effective_until'])
            : null;

        return DB::transaction(function () use ($validated, $effectiveFrom, $effectiveUntil): PlanPrice {
            $plan = Plan::query()
                ->whereKey($validated['plan_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $currency = Currency::query()
                ->where('code', $validated['currency_code'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $currency->is_active) {
                throw ValidationException::withMessages([
                    'currency_code' => 'Le tarif doit utiliser une devise active.',
                ]);
            }

            $matchingPrices = PlanPrice::query()
                ->where('plan_id', $plan->getKey())
                ->where('currency_code', $currency->code)
                ->where('billing_period_unit', $validated['billing_period_unit'])
                ->where('billing_period_count', $validated['billing_period_count'])
                ->whereNull('archived_at')
                ->lockForUpdate();

            $priorPrices = (clone $matchingPrices)
                ->where('effective_from', '<', $effectiveFrom)
                ->where(function ($query) use ($effectiveFrom): void {
                    $query->whereNull('effective_until')
                        ->orWhere('effective_until', '>=', $effectiveFrom);
                })
                ->get();

            foreach ($priorPrices as $priorPrice) {
                $priorPrice->effective_until = $effectiveFrom->copy()->subSecond();
                $priorPrice->save();
            }

            $futurePrices = (clone $matchingPrices)
                ->where('effective_from', '>=', $effectiveFrom)
                ->when(
                    $effectiveUntil !== null,
                    fn ($query) => $query->where('effective_from', '<=', $effectiveUntil),
                )
                ->exists();

            if ($futurePrices) {
                throw ValidationException::withMessages([
                    'effective_from' => 'La période chevauche un tarif déjà planifié.',
                ]);
            }

            return PlanPrice::query()->create([
                ...$validated,
                'effective_from' => $effectiveFrom,
                'effective_until' => $effectiveUntil,
            ]);
        });
    }
}
