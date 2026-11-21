<?php

namespace App\Http\Controllers\Orders;

use App\Domain\Catalog\Services\MoneyFormatter;
use App\Domain\Orders\Actions\AbandonPendingOrder;
use App\Domain\Orders\Actions\ExpirePendingOrders;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Services\OrderAccessService;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

class ShowPublicOrderController extends Controller
{
    public function __invoke(
        MoneyFormatter $moneyFormatter,
        OrderAccessService $orderAccessService,
        PaymentGateway $paymentGateway,
        string $publicId,
        string $token,
    ): View {
        $order = $orderAccessService->findByAccessToken($publicId, $token);

        $expiresAt = $order->expires_at ?? $order->created_at->copy()->addHours(24);
        $isExpired = $order->status === OrderStatus::PendingPayment && $expiresAt->lte(now());

        return view('orders.show', [
            'abandonUrl' => URL::temporarySignedRoute(
                'orders.abandon',
                $order->guest_access_expires_at,
                ['publicId' => $order->public_id, 'token' => $token],
            ),
            'isExpired' => $isExpired,
            'moneyFormatter' => $moneyFormatter,
            'order' => $order,
            'paypalCheckoutUrl' => $paymentGateway->isConfigured() && ! $isExpired && $order->status === OrderStatus::PendingPayment
                ? URL::temporarySignedRoute(
                    'payments.paypal.start',
                    $order->guest_access_expires_at,
                    ['publicId' => $order->public_id, 'token' => $token],
                )
                : null,
        ]);
    }

    public function abandon(
        ExpirePendingOrders $expirePendingOrders,
        AbandonPendingOrder $abandonPendingOrder,
        OrderAccessService $orderAccessService,
        string $publicId,
        string $token,
    ): RedirectResponse {
        $order = $orderAccessService->findByAccessToken($publicId, $token);

        $expirePendingOrders->execute($order);
        $abandonPendingOrder->execute($order);

        return redirect()->to(URL::temporarySignedRoute(
            'orders.show',
            $order->guest_access_expires_at,
            ['publicId' => $order->public_id, 'token' => $token],
        ));
    }
}
