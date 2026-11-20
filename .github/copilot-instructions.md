# Instructions GitHub Copilot

## Contexte vérifié

Au 2026-11-20, le dépôt utilise PHP 8.4.0, Laravel 13.43.0, Filament 5.11.4, Livewire 4.4.8, Filament Shield 4.3.1, Spatie Laravel Permission 8.5.0, PHPUnit 12.5.45 et Laravel Pint 1.32.3. La cible est Laravel 13+ et Filament 4+ ; respecte toujours la major réellement installée et vérifie la compatibilité avant toute mise à niveau.

Tailwind CSS 4 et Vite 8 sont déclarés dans `package.json`. Au dernier contrôle, `node_modules` et `package-lock.json` étaient absents. Ne suppose pas que les dépendances JavaScript sont installées.

## Produit et architecture

- L'application est un monolithe Laravel modulaire, destiné uniquement à la vente et à la gestion d'abonnements à des services audiovisuels autorisés.
- Il n'existe qu'un panel Filament d'administration, identifié par `admin`. Les espaces publics, client et revendeur utilisent Blade, Livewire et Tailwind ; ne crée pas de panel Filament distinct pour ces espaces.
- L'accès au panel est réservé à `super_admin`, `admin` et `support`. `finance`, `reseller` et `customer` n'y ont pas accès. Toute modification de cette règle nécessite une validation explicite.
- Organise la logique métier par domaine sous `app/Domain/{Domain}` lorsque le besoin est concret. Conserve les conventions et modèles existants ; ne déplace pas le code sans raison.
- Garde contrôleurs, composants Livewire et Resources Filament minces. Place les opérations métier dans des Actions ou Services cohérents, injectés par dépendance.
- Utilise les instructions spécialisées : [Laravel](./instructions/laravel.instructions.md), [Filament](./instructions/filament.instructions.md), [front-end](./instructions/frontend.instructions.md) et [tests](./instructions/testing.instructions.md).

## Règles de travail

- Avant chaque tâche, inspecte le code et les consignes pertinents, puis présente un plan court avant les modifications.
- Respecte les changements préexistants et le travail non commité. Ne remplace pas des instructions ou des fichiers existants sans les lire et préserver leurs règles utiles.
- Ne supprime pas de données ou de fichiers sans accord explicite. N'exécute pas de commande destructive ni de migration sur une base partagée sans approbation.
- Ne modifie pas les dépendances sans approbation. Vérifie la compatibilité des versions avant toute installation ou mise à niveau.
- Après chaque tâche, lance les vérifications pertinentes les plus ciblées et rapporte les résultats réellement obtenus. Ne prétends pas qu'un test, un build ou une commande a réussi s'il n'a pas été exécuté.

## Sécurité et conformité

- Autorise côté serveur chaque opération avec policies et permissions ; ne te fie jamais au masquage d'un bouton. Vérifie systématiquement la propriété et le périmètre des données pour prévenir les IDOR.
- Valide les entrées, limite l'assignation de masse, applique CSRF et rate limiting aux flux appropriés et échappe les sorties HTML.
- Préviens XSS en échappant les sorties et en assainissant strictement tout HTML riche accepté.
- Ne stocke ni ne journalise secrets, mots de passe, jetons de paiement ou données de carte bancaire. Ne mets aucun secret dans le code ou les fixtures.
- Un abonnement ne peut être activé qu'après une confirmation serveur fiable du paiement, jamais depuis une simple redirection du navigateur.
- Vérifie signature, idempotence et transitions d'état des webhooks. Les opérations financières doivent être atomiques.
- N'intègre que des fournisseurs de contenus audiovisuels dûment autorisés.
- N'exécute jamais de PHP ou Blade arbitraire depuis un éditeur de contenu ; privilégie des données et composants explicitement autorisés.
