<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Services\GenerateInvoicePdf;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice, GenerateInvoicePdf $generateInvoicePdf): StreamedResponse
    {
        $path = $generateInvoicePdf->execute($invoice);
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), Response::HTTP_NOT_FOUND);

        return $disk->download($path, $invoice->invoice_number.'.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
