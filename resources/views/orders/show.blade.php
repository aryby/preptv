<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Commande {{ $order->public_id }} | {{ config('app.name') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-slate-950 text-white antialiased">
        <main class="mx-auto max-w-3xl px-6 py-12">
            <a href="{{ route('catalog.offers') }}" class="text-sm text-slate-300 hover:text-white">
                Retour aux offres
            </a>

            <section class="mt-6 rounded-2xl border border-slate-700 bg-slate-900 p-6">
                <h1 class="text-3xl font-bold">Commande</h1>
                <p class="mt-3 break-all text-slate-300">Référence : {{ $order->public_id }}</p>

                @if ($isExpired)
                    <p class="mt-4 rounded-lg bg-slate-800 p-4 text-slate-200">
                        Cette commande a expiré. Créez une nouvelle commande pour continuer.
                    </p>
                @elseif ($order->status === \App\Domain\Orders\Enums\OrderStatus::PendingPayment)
                    <div class="mt-4 space-y-4">
                        <p class="rounded-lg bg-amber-950/40 p-4 text-amber-100">
                            En attente de paiement. Aucun abonnement n’est activé.
                        </p>
                        @if ($paypalCheckoutUrl)
                            <form method="POST" action="{{ $paypalCheckoutUrl }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500"
                                >
                                    Payer avec PayPal
                                </button>
                            </form>
                        @endif
                        @error('payment')
                            <p class="rounded-lg bg-red-950/40 p-4 text-red-100">{{ $message }}</p>
                        @enderror
                        <form method="POST" action="{{ $abandonUrl }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800"
                            >
                                Abandonner cette commande
                            </button>
                        </form>
                    </div>
                @elseif ($order->status === \App\Domain\Orders\Enums\OrderStatus::Expired)
                    <p class="mt-4 rounded-lg bg-slate-800 p-4 text-slate-200">
                        Cette commande a expiré. Créez une nouvelle commande pour continuer.
                    </p>
                @elseif ($order->status === \App\Domain\Orders\Enums\OrderStatus::Abandoned)
                    <p class="mt-4 rounded-lg bg-slate-800 p-4 text-slate-200">
                        Cette commande a été abandonnée.
                    </p>
                @else
                    <p class="mt-4 rounded-lg bg-slate-800 p-4 text-slate-200">
                        Statut : {{ $order->status->value }}
                    </p>
                @endif

                <h2 class="mt-8 text-xl font-semibold">Détail</h2>
                <ul class="mt-3 divide-y divide-slate-700">
                    @foreach ($order->items as $item)
                        <li class="flex justify-between gap-4 py-4">
                            <span>
                                {{ $item->plan_name_snapshot }} —
                                {{ $item->billing_period_unit->label($item->billing_period_count) }}
                                × {{ $item->quantity }}
                            </span>
                            <span class="whitespace-nowrap">
                                {{ $moneyFormatter->format($item->total_minor, $item->currency) }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-6 flex justify-between border-t border-slate-700 pt-4 text-lg font-semibold">
                    <span>Total</span>
                    <span>{{ $moneyFormatter->format($order->total_minor, $order->currency) }}</span>
                </p>
            </section>
        </main>
    </body>
</html>
