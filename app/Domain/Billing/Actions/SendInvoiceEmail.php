<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Exceptions\InvoiceProcessingException;
use App\Domain\Billing\Notifications\InvoiceIssuedNotification;
use App\Domain\Messaging\Enums\EmailLogStatus;
use App\Models\EmailLog;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SendInvoiceEmail
{
    public function execute(Invoice $invoice): void
    {
        $invoice->refresh();

        if ($invoice->email_sent_at !== null) {
            return;
        }

        $recipient = $invoice->buyer_snapshot['email'] ?? null;

        if (! is_string($recipient) || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvoiceProcessingException('invoice_recipient_email_invalid');
        }

        if ($invoice->pdf_path === null) {
            throw new InvoiceProcessingException('invoice_pdf_missing');
        }

        $linkLifetime = filter_var(
            config('billing.invoice.download_link_lifetime_minutes', 10080),
            FILTER_VALIDATE_INT,
        );

        if ($linkLifetime === false || $linkLifetime < 1 || $linkLifetime > 10080) {
            throw new InvoiceProcessingException('invoice_download_lifetime_invalid');
        }

        $downloadUrl = URL::temporarySignedRoute(
            'invoices.download',
            now()->addMinutes($linkLifetime),
            ['invoice' => $invoice->getKey()],
        );

        try {
            Notification::route('mail', $recipient)
                ->notify(new InvoiceIssuedNotification($invoice, $downloadUrl));
        } catch (TransportExceptionInterface) {
            $this->recordEmail($invoice, $recipient, EmailLogStatus::Failed, 'invoice_email_delivery_failed');

            throw new InvoiceProcessingException('invoice_email_delivery_failed');
        }

        DB::transaction(function () use ($invoice, $recipient): void {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            if ($lockedInvoice->email_sent_at !== null) {
                return;
            }

            $this->recordEmail($lockedInvoice, $recipient, EmailLogStatus::Sent, null);
            $lockedInvoice->forceFill(['email_sent_at' => now()])->save();
        });
    }

    private function recordEmail(
        Invoice $invoice,
        string $recipient,
        EmailLogStatus $status,
        ?string $errorCode,
    ): void {
        Model::unguarded(fn (): EmailLog => EmailLog::query()->create([
            'user_id' => $invoice->user_id,
            'order_id' => $invoice->order_id,
            'template_key_snapshot' => 'invoice.issued',
            'recipient_email' => $recipient,
            'status' => $status,
            'error_code' => $errorCode,
            'sent_at' => $status === EmailLogStatus::Sent ? now() : null,
        ]));
    }
}
