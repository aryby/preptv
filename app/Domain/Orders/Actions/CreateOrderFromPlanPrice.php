<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Enums\PlanStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlanPrice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrderFromPlanPrice
{
    /**
     * @param  array{name?: mixed, email?: mixed}  $guestSnapshot
     */
    public function execute(
        int $planPriceId,
        string $currencyCode,
        array $guestSnapshot,
        ?User $user = null,
        int $quantity = 1,
        ?string $guestAccessToken = null,
    ): Order {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'La quantité doit être supérieure à zéro.',
            ]);
        }

        $customerSnapshot = $user === null
            ? Validator::make($guestSnapshot, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
            ])->validate()
            : ['name' => $user->name, 'email' => $user->email];

        return DB::transaction(function () use ($planPriceId, $currencyCode, $customerSnapshot, $user, $quantity, $guestAccessToken): Order {
            $price = PlanPrice::query()
                ->with(['plan', 'currency'])
                ->lockForUpdate()
                ->findOrFail($planPriceId);
            $now = now();

            if (
                $price->plan === null
                || $price->plan->status !== PlanStatus::Active
                || ! $price->plan->is_public
                || $price->archived_at !== null
                || $price->effective_from->isAfter($now)
                || ($price->effective_until !== null && $price->effective_until->isBefore($now))
            ) {
                throw ValidationException::withMessages([
                    'planPrice' => 'Cette offre n’est plus disponible.',
                ]);
            }

            if (
                $price->currency === null
                || ! $price->currency->is_active
                || $price->currency_code !== $currencyCode
            ) {
                throw ValidationException::withMessages([
                    'currencyCode' => 'La devise sélectionnée n’est pas disponible pour cette offre.',
                ]);
            }

            if ($price->billing_period_count < 1 || $price->amount_minor < 0) {
                throw ValidationException::withMessages([
                    'planPrice' => 'Le tarif ou la durée de cette offre est invalide.',
                ]);
            }

            if ($price->amount_minor > intdiv(PHP_INT_MAX, $quantity)) {
                throw ValidationException::withMessages([
                    'quantity' => 'Le montant total dépasse la limite autorisée.',
                ]);
            }

            $subtotalMinor = $price->amount_minor * $quantity;
            $createdAt = now();

            return Model::unguarded(function () use ($price, $customerSnapshot, $user, $quantity, $subtotalMinor, $guestAccessToken, $createdAt): Order {
                $order = Order::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'user_id' => $user?->getKey(),
                    'currency_code' => $price->currency_code,
                    'status' => OrderStatus::PendingPayment,
                    'subtotal_minor' => $subtotalMinor,
                    'tax_minor' => 0,
                    'total_minor' => $subtotalMinor,
                    'customer_snapshot' => $customerSnapshot,
                    'guest_access_token_hash' => $guestAccessToken === null
                        ? null
                        : hash('sha256', $guestAccessToken),
                    'guest_access_expires_at' => $guestAccessToken === null
                        ? null
                        : $createdAt->copy()->addDays(7),
                    'expires_at' => $createdAt->copy()->addHours(24),
                ]);

                OrderItem::query()->create([
                    'order_id' => $order->getKey(),
                    'line_number' => 1,
                    'plan_id' => $price->plan_id,
                    'plan_price_id' => $price->getKey(),
                    'currency_code' => $price->currency_code,
                    'plan_name_snapshot' => $price->plan->name,
                    'description_snapshot' => $price->plan->description,
                    'billing_period_unit' => $price->billing_period_unit,
                    'billing_period_count' => $price->billing_period_count,
                    'quantity' => $quantity,
                    'unit_amount_minor' => $price->amount_minor,
                    'subtotal_minor' => $subtotalMinor,
                    'tax_rate_basis_points' => 0,
                    'tax_minor' => 0,
                    'tax_label_snapshot' => null,
                    'total_minor' => $subtotalMinor,
                ]);

                return $order->load('items');
            });
        });
    }
}
