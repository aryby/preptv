<?php

namespace App\Http\Controllers\Orders;

use App\Domain\Catalog\Services\MoneyFormatter;
use App\Domain\Orders\Actions\AbandonPendingOrder;
use App\Domain\Orders\Actions\ExpirePendingOrders;
use App\Domain\Orders\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

class ShowPublicOrderController extends Controller
{
    public function __invoke(
        MoneyFormatter $moneyFormatter,
        string $publicId,
        string $token,
    ): View {
        $order = $this->authorizedOrder($publicId, $token);

        $expiresAt = $order->expires_at ?? $order->created_at->copy()->addHours(24);

        return view('orders.show', [
            'abandonUrl' => URL::temporarySignedRoute(
                'orders.abandon',
                $order->guest_access_expires_at,
                ['publicId' => $order->public_id, 'token' => $token],
            ),
            'isExpired' => $order->status === OrderStatus::PendingPayment && $expiresAt->lte(now()),
            'moneyFormatter' => $moneyFormatter,
            'order' => $order,
        ]);
    }

    public function abandon(
        ExpirePendingOrders $expirePendingOrders,
        AbandonPendingOrder $abandonPendingOrder,
        string $publicId,
        string $token,
    ): RedirectResponse {
        $order = $this->authorizedOrder($publicId, $token);

        $expirePendingOrders->execute($order);
        $abandonPendingOrder->execute($order);

        return redirect()->to(URL::temporarySignedRoute(
            'orders.show',
            $order->guest_access_expires_at,
            ['publicId' => $order->public_id, 'token' => $token],
        ));
    }

    private function authorizedOrder(string $publicId, string $token): Order
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
