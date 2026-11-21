<?php

namespace App\Domain\Orders\Services;

use App\Models\Order;

class OrderAccessService
{
    public function findByAccessToken(string $publicId, string $token): Order
    {
        $order = Order::query()
            ->with(['currency', 'items.currency'])
            ->where('public_id', $publicId)
            ->firstOrFail();

        abort_unless(
            $order->guest_access_token_hash !== null
                && $order->guest_access_expires_at?->isFuture()
                && hash_equals($order->guest_access_token_hash, hash('sha256', $token)),
            404,
        );

        return $order;
    }
}
