# Prompt 01 — Instructions persistantes GitHub Copilot

Crée ou améliore les instructions persistantes du dépôt pour guider les prochains travaux.

Fichiers souhaités :
- `.github/copilot-instructions.md`
- `.github/instructions/laravel.instructions.md`
- `.github/instructions/filament.instructions.md`
- `.github/instructions/frontend.instructions.md`
- `.github/instructions/testing.instructions.md`

Avant de créer un fichier, vérifie s'il existe. Ne remplace jamais silencieusement des instructions existantes : propose une fusion et conserve les règles utiles.

Les instructions doivent couvrir :
- Laravel 12 et PHP 8.3+, en respectant les versions réellement détectées ;
- Filament v4 et un panel unique ;
- Blade, Livewire et Tailwind ;
- architecture modulaire orientée domaines ;
- contrôleurs, composants Livewire et Resources Filament minces ;
- logique métier dans Actions/Services ;
- injection de dépendances, validation, policies et Form Requests ;
- transactions DB pour les opérations financières ;
- enums PHP pour les statuts ;
- tests Pest ou PHPUnit selon le projet ;
- conventions PSR-12 et Laravel Pint ;
- sécurité, protection IDOR, XSS, CSRF, mass assignment et rate limiting ;
- webhooks signés et idempotents ;
- journalisation sans secrets ni données de carte bancaire ;
- conformité : intégration uniquement avec des fournisseurs de contenus autorisés.

Interdictions :
- pas de `migrate:fresh` ;
- pas de suppression de données ou de fichiers sans accord ;
- pas de secrets dans le code ;
- pas d'activation d'abonnement depuis une simple redirection navigateur ;
- pas d'exécution de PHP/Blade arbitraire depuis un éditeur de contenu.

Ajoute une règle : avant chaque tâche, inspecter le code existant et proposer un plan court ; après chaque tâche, exécuter les vérifications pertinentes et rapporter les résultats réels.

À la fin, affiche le contenu des fichiers créés/modifiés et attends validation.
