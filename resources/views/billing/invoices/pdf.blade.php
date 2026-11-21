<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Facture {{ $invoice->invoice_number }}</title>
        <style>
            @page { margin: 28px; }
            body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
            h1 { font-size: 24px; margin: 0 0 8px; }
            h2 { font-size: 13px; margin: 0 0 8px; }
            p { margin: 3px 0; }
            .muted { color: #64748b; }
            .header, .parties, .totals { width: 100%; }
            .header td, .parties td { vertical-align: top; width: 50%; }
            .parties { margin: 24px 0; }
            .lines { border-collapse: collapse; width: 100%; }
            .lines th, .lines td { border-bottom: 1px solid #cbd5e1; padding: 8px 5px; text-align: left; }
            .lines th { background: #f1f5f9; }
            .right { text-align: right !important; }
            .totals { border-collapse: collapse; margin-top: 16px; }
            .totals td { padding: 5px; }
            .grand-total { border-top: 1px solid #334155; font-size: 13px; font-weight: bold; }
            .address { white-space: pre-line; }
        </style>
    </head>
    <body>
        <table class="header">
            <tr>
                <td>
                    <h1>Facture</h1>
                    <p><strong>{{ $invoice->invoice_number }}</strong></p>
                    <p class="muted">Émise le {{ $invoice->issued_at->format('d/m/Y') }}</p>
                    <p class="muted">Commande : {{ $invoice->order?->public_id ?? '—' }}</p>
                </td>
                <td class="right">
                    <h2>{{ $invoice->seller_snapshot['name'] ?? '' }}</h2>
                    <p class="address">{{ $invoice->seller_snapshot['address'] ?? '' }}</p>
                    @if (filled($invoice->seller_snapshot['email'] ?? null))
                        <p>{{ $invoice->seller_snapshot['email'] }}</p>
                    @endif
                    @if (filled($invoice->seller_snapshot['tax_identifier'] ?? null))
                        <p>Identifiant fiscal : {{ $invoice->seller_snapshot['tax_identifier'] }}</p>
                    @endif
                    @if (filled($invoice->seller_snapshot['registration_number'] ?? null))
                        <p>Immatriculation : {{ $invoice->seller_snapshot['registration_number'] }}</p>
                    @endif
                    @if (filled($invoice->seller_snapshot['country_code'] ?? null))
                        <p>{{ $invoice->seller_snapshot['country_code'] }}</p>
                    @endif
                </td>
            </tr>
        </table>

        <table class="parties">
            <tr>
                <td>
                    <h2>Facturé à</h2>
                    <p><strong>{{ $invoice->buyer_snapshot['name'] ?? '' }}</strong></p>
                    @if (filled($invoice->buyer_snapshot['email'] ?? null))
                        <p>{{ $invoice->buyer_snapshot['email'] }}</p>
                    @endif
                    @if (is_array($invoice->buyer_snapshot['billing_address'] ?? null))
                        @foreach ($invoice->buyer_snapshot['billing_address'] as $addressLine)
                            @if (is_string($addressLine) && $addressLine !== '')
                                <p>{{ $addressLine }}</p>
                            @endif
                        @endforeach
                    @endif
                </td>
                <td>
                    <h2>Paiement</h2>
                    <p>Statut : réglée</p>
                    <p>Devise : {{ $invoice->currency_code }}</p>
                </td>
            </tr>
        </table>

        <table class="lines">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Quantité</th>
                    <th class="right">Prix unitaire</th>
                    <th class="right">Taxe</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description_snapshot }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">{{ $moneyFormatter->format($item->unit_amount_minor, $invoice->currency) }}</td>
                        <td class="right">
                            {{ $moneyFormatter->format($item->tax_minor, $invoice->currency) }}
                            @if ($item->tax_rate_basis_points > 0)
                                <span class="muted">
                                    ({{ intdiv($item->tax_rate_basis_points, 100) }},{{ str_pad((string) ($item->tax_rate_basis_points % 100), 2, '0', STR_PAD_LEFT) }} %)
                                </span>
                            @endif
                            @if ($item->tax_label_snapshot)
                                <span class="muted">{{ $item->tax_label_snapshot }}</span>
                            @endif
                        </td>
                        <td class="right">{{ $moneyFormatter->format($item->total_minor, $invoice->currency) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td>Sous-total</td>
                <td class="right">{{ $moneyFormatter->format($invoice->subtotal_minor, $invoice->currency) }}</td>
            </tr>
            <tr>
                <td>Taxes selon les données de commande</td>
                <td class="right">{{ $moneyFormatter->format($invoice->tax_minor, $invoice->currency) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total réglé</td>
                <td class="right">{{ $moneyFormatter->format($invoice->total_minor, $invoice->currency) }}</td>
            </tr>
        </table>
    </body>
</html>
