<section class="space-y-8">
    @if ($currencies->isEmpty())
        <p class="rounded-lg border border-slate-700 p-5 text-slate-300">
            Aucune devise active n’est disponible pour le moment.
        </p>
    @else
        <div class="max-w-xs">
            <label for="currencyCode" class="mb-2 block text-sm font-medium text-slate-200">
                Devise
            </label>
            <select
                id="currencyCode"
                wire:model.live="currencyCode"
                class="w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-white"
            >
                <option value="">Choisir une devise</option>
                @foreach ($currencies as $availableCurrency)
                    <option value="{{ $availableCurrency->code }}">
                        {{ $availableCurrency->name }} ({{ $availableCurrency->code }})
                    </option>
                @endforeach
            </select>
            @error('currencyCode')
                <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
            @enderror
        </div>

        @if ($currency === null)
            <p class="rounded-lg border border-amber-700 bg-amber-950/40 p-5 text-amber-100">
                Aucune devise par défaut n’est configurée. Sélectionnez une devise pour consulter les offres.
            </p>
        @elseif ($plans->isEmpty())
            <p class="rounded-lg border border-slate-700 p-5 text-slate-300">
                Aucune offre publiée n’est disponible dans cette devise.
            </p>
        @else
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($plans as $plan)
                    <article class="rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-lg">
                        <h2 class="text-2xl font-semibold">{{ $plan->name }}</h2>
                        @if ($plan->description)
                            <p class="mt-3 text-slate-300">{{ $plan->description }}</p>
                        @endif

                        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm text-slate-300">
                            <div>
                                <dt>Appareils</dt>
                                <dd class="font-medium text-white">{{ $plan->device_limit }}</dd>
                            </div>
                            <div>
                                <dt>Sessions simultanées</dt>
                                <dd class="font-medium text-white">{{ $plan->concurrent_session_limit }}</dd>
                            </div>
                        </dl>

                        <ul class="mt-6 divide-y divide-slate-700">
                            @foreach ($plan->planPrices as $price)
                                <li class="flex items-center justify-between gap-4 py-4">
                                    <span class="text-slate-200">
                                        {{ $price->billing_period_unit->label($price->billing_period_count) }}
                                    </span>
                                    <span class="font-semibold text-white">
                                        {{ $moneyFormatter->format($price->amount_minor, $price->currency) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        @endif
    @endif
</section>
