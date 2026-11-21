<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class ExpirePendingOrders
{
    public function execute(?Order $order = null): int
    {
        $now = now();
        $query = Order::query()
            ->where('status', OrderStatus::PendingPayment->value)
            ->where(function (Builder $query) use ($now): void {
                $query->where('expires_at', '<=', $now)
                    ->orWhere(function (Builder $query) use ($now): void {
                        $query->whereNull('expires_at')
                            ->where('created_at', '<=', $now->copy()->subHours(24));
                    });
            });

        if ($order !== null) {
            $query->whereKey($order->getKey());
        }

        return $query->update([
            'status' => OrderStatus::Expired->value,
            'updated_at' => $now,
        ]);
    }
}
