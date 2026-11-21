<?php

namespace App\Domain\Orders\Notifications;

use App\Domain\Catalog\Services\MoneyFormatter;
use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification
{
    public function __construct(
        public Order $order,
        public string $orderUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $customerName = $this->order->customer_snapshot['name'] ?? 'client';
        $total = app(MoneyFormatter::class)->format($this->order->total_minor, $this->order->currency);

        return (new MailMessage)
            ->subject("Confirmation de commande {$this->order->public_id}")
            ->greeting("Bonjour {$customerName},")
            ->line('Votre commande a bien été reçue.')
            ->line("Référence : {$this->order->public_id}")
            ->line("Montant total : {$total}")
            ->line('Le paiement est en attente. Aucun abonnement n’a été activé.')
            ->action('Consulter ma commande', $this->orderUrl)
            ->line('Ce lien sécurisé expire dans sept jours.');
    }
}
