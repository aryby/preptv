---
applyTo: "app/Providers/Filament/**/*.php,app/Filament/**/*.php,app/Policies/**/*.php"
---

# Instructions Filament

- La cible est Filament v4+ ; le panel installé est Filament 5.11.4 avec un unique panel d'administration `admin`. Vérifie les versions Composer avant d'utiliser une API ; n'applique pas un exemple Filament 4 sans confirmer sa compatibilité.
- N'ajoute pas de panel client ou revendeur. Ces espaces utilisent Blade et Livewire.
- L'accès au panel est réservé aux rôles `super_admin`, `admin` et `support`. `finance`, `reseller` et `customer` sont exclus. Toute évolution des rôles autorisés doit être approuvée et couverte par des tests.
- Utilise Filament Shield et Spatie Permission déjà installés pour les permissions par rôle. Protège Resources, Pages et Widgets côté serveur avec les permissions et policies ; masquer une action dans l'interface ne suffit pas.
- Garde Resources, Pages, Widgets et composants Filament minces. Délègue validation métier et écritures aux Actions/Services du domaine.
- Pour les données sensibles ou appartenant à un utilisateur/revendeur, applique les policies et le scope des requêtes, y compris lors d'accès direct par URL. Charge les relations utiles et évite les requêtes N+1.
- Protège les actions destructives par autorisation, confirmation et règles métier. Ne rends pas possible la suppression en masse de rôles privilégiés ni la rétrogradation accidentelle du dernier `super_admin`.
- N'expose jamais de secrets, jetons de paiement ou données de carte dans les formulaires, tables, widgets, exports ou journaux.
- N'exécute pas de PHP ou Blade arbitraire depuis les contenus administrables. Utilise des composants explicitement autorisés et des données validées.
