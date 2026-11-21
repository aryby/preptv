<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Offres | {{ config('app.name') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-950 text-white antialiased">
        <main class="mx-auto max-w-7xl px-6 py-12">
            <header class="mb-10">
                <a href="/" class="text-sm text-slate-300 hover:text-white">Accueil</a>
                <h1 class="mt-4 text-4xl font-bold tracking-tight">Nos offres</h1>
                <p class="mt-3 max-w-2xl text-slate-300">
                    Choisissez une offre et une durée disponibles dans votre devise.
                </p>
            </header>

            <livewire:catalog.public-offers />
        </main>

        @livewireScripts
    </body>
</html>
