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
                                    <div class="flex items-center gap-4">
                                        <span class="font-semibold text-white">
                                            {{ $moneyFormatter->format($price->amount_minor, $price->currency) }}
                                        </span>
                                        <button
                                            type="button"
                                            wire:click="choosePrice({{ $price->id }})"
                                            class="rounded-lg bg-indigo-500 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-400"
                                        >
                                            Choisir
                                        </button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        @endif
    @endif

    @error('selectedPlanPriceId')
        <p class="rounded-lg border border-red-800 bg-red-950/40 p-4 text-red-200">{{ $message }}</p>
    @enderror
    @error('planPrice')
        <p class="rounded-lg border border-red-800 bg-red-950/40 p-4 text-red-200">{{ $message }}</p>
    @enderror
    @error('checkout')
        <p class="rounded-lg border border-red-800 bg-red-950/40 p-4 text-red-200">{{ $message }}</p>
    @enderror
    @error('currencyCode')
        <p class="rounded-lg border border-red-800 bg-red-950/40 p-4 text-red-200">{{ $message }}</p>
    @enderror

    @if ($selectedPlanPrice)
        <section class="rounded-2xl border border-slate-700 bg-slate-900 p-6">
            <h2 class="text-2xl font-semibold">Finaliser la commande</h2>
            <p class="mt-2 text-slate-300">
                {{ $selectedPlanPrice->plan->name }} —
                {{ $selectedPlanPrice->billing_period_unit->label($selectedPlanPrice->billing_period_count) }},
                {{ $moneyFormatter->format($selectedPlanPrice->amount_minor, $selectedPlanPrice->currency) }}
            </p>

            <form wire:submit="placeOrder" class="mt-6 max-w-xl space-y-4">
                @auth
                    <p class="text-sm text-slate-300">
                        Confirmation envoyée à {{ auth()->user()->email }}.
                    </p>
                @else
                    <div>
                        <label for="billingName" class="mb-2 block text-sm font-medium text-slate-200">
                            Nom complet
                        </label>
                        <input
                            id="billingName"
                            type="text"
                            wire:model="billingName"
                            autocomplete="name"
                            maxlength="255"
                            required
                            class="w-full rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 text-white"
                        >
                        @error('billingName')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="billingEmail" class="mb-2 block text-sm font-medium text-slate-200">
                            Adresse e-mail
                        </label>
                        <input
                            id="billingEmail"
                            type="email"
                            wire:model="billingEmail"
                            autocomplete="email"
                            maxlength="255"
                            required
                            class="w-full rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 text-white"
                        >
                        @error('billingEmail')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                @endauth

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="placeOrder"
                    class="rounded-lg bg-emerald-500 px-5 py-3 font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-50"
                >
                    Confirmer la commande
                </button>
            </form>
        </section>
    @elseif ($selectedPlanPriceId !== '')
        <p class="rounded-lg border border-amber-700 bg-amber-950/40 p-4 text-amber-100">
            Cette durée n’est plus disponible. Choisissez une offre à jour.
        </p>
    @endif

    @if ($orderUrl !== '')
        <section class="rounded-2xl border border-emerald-700 bg-emerald-950/30 p-6">
            <h2 class="text-2xl font-semibold">Commande confirmée</h2>
            <p class="mt-2 text-slate-200">Référence : {{ $orderReference }}</p>
            <p class="mt-2 text-slate-300">
                Votre commande est en attente de paiement. Aucun abonnement n’est activé à ce stade.
            </p>
            @if ($emailDeliveryFailed)
                <p class="mt-3 text-amber-200">
                    L’e-mail de confirmation n’a pas pu être envoyé. Utilisez le lien sécurisé ci-dessous.
                </p>
            @else
                <p class="mt-3 text-slate-300">Un e-mail de confirmation a été envoyé.</p>
            @endif
            <a
                href="{{ $orderUrl }}"
                class="mt-5 inline-block rounded-lg bg-indigo-500 px-5 py-3 font-semibold text-white hover:bg-indigo-400"
            >
                Consulter ma commande
            </a>
        </section>
    @endif
</section>
