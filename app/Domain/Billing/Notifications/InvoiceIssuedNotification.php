<?php

namespace App\Domain\Billing\Notifications;

use App\Domain\Catalog\Services\MoneyFormatter;
use App\Models\Invoice;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceIssuedNotification extends Notification
{
    public function __construct(
        public Invoice $invoice,
        public string $downloadUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->invoice->loadMissing('currency');
        $total = app(MoneyFormatter::class)->format($this->invoice->total_minor, $this->invoice->currency);

        return (new MailMessage)
            ->subject("Facture {$this->invoice->invoice_number}")
            ->greeting('Bonjour '.($this->invoice->buyer_snapshot['name'] ?? 'client').',')
            ->line("Votre facture {$this->invoice->invoice_number} est disponible.")
            ->line("Total réglé : {$total}")
            ->action('Télécharger la facture', $this->downloadUrl)
            ->attachFromStorageDisk(
                'local',
                $this->invoice->pdf_path,
                $this->invoice->invoice_number.'.pdf',
                ['mime' => 'application/pdf'],
            );
    }
}
