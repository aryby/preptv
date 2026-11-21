<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Exceptions\InvoiceProcessingException;
use App\Domain\Catalog\Services\MoneyFormatter;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdf
{
    public function execute(Invoice $invoice): string
    {
        $invoice->loadMissing(['items.currency', 'currency', 'order']);
        $disk = Storage::disk('local');

        if ($invoice->pdf_path !== null && $disk->exists($invoice->pdf_path)) {
            if ($invoice->pdf_sha256 !== null && ! hash_equals($invoice->pdf_sha256, hash('sha256', $disk->get($invoice->pdf_path)))) {
                throw new InvoiceProcessingException('invoice_pdf_checksum_mismatch');
            }

            return $invoice->pdf_path;
        }

        $pdfContents = Pdf::loadView('billing.invoices.pdf', [
            'invoice' => $invoice,
            'moneyFormatter' => app(MoneyFormatter::class),
        ])
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
            ])
            ->output();
        $path = 'invoices/'.$invoice->invoice_number.'.pdf';

        if (! $disk->put($path, $pdfContents)) {
            throw new InvoiceProcessingException('invoice_pdf_storage_failed');
        }

        $invoice->forceFill([
            'pdf_path' => $path,
            'pdf_sha256' => hash('sha256', $pdfContents),
            'pdf_generated_at' => now(),
        ])->save();

        return $path;
    }
}
