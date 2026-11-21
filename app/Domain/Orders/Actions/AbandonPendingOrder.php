<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class AbandonPendingOrder
{
    public function execute(Order $order): bool
    {
        $now = now();

        return Order::query()
            ->whereKey($order->getKey())
            ->where('status', OrderStatus::PendingPayment->value)
            ->where(function (Builder $query) use ($now): void {
                $query->where('expires_at', '>', $now)
                    ->orWhere(function (Builder $query) use ($now): void {
                        $query->whereNull('expires_at')
                            ->where('created_at', '>', $now->copy()->subHours(24));
                    });
            })
            ->update([
                'status' => OrderStatus::Abandoned->value,
                'updated_at' => $now,
            ]) === 1;
    }
}
