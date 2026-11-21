<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Paiement en vérification | {{ config('app.name') }}</title>
    </head>
    <body class="min-h-screen bg-slate-950 text-white antialiased">
        <main class="mx-auto max-w-2xl px-6 py-16">
            <h1 class="text-3xl font-bold">Paiement en cours de vérification</h1>
            <p class="mt-4 text-slate-300">
                PayPal nous transmettra la confirmation sécurisée. La commande ne sera marquée payée qu’après vérification de cet événement.
            </p>
            <a href="{{ route('catalog.offers') }}" class="mt-8 inline-block text-slate-200 underline">Retour aux offres</a>
        </main>
    </body>
</html>
