# Prompt 00 — Audit initial du dépôt

Agis comme un architecte logiciel senior spécialisé en Laravel 13, PHP 8.3+ et Filament v4.

Avant toute modification, inspecte le dépôt actuel :
- `composer.json` et `composer.lock`
- version PHP/Laravel/Filament réellement installée
- structure `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `resources/`, `tests/`
- migrations, modèles, policies, middleware, providers et panel Filament
- dépendances déjà présentes et éventuelles fonctionnalités existantes
- état Git et fichiers modifiés non commités.

Le produit cible est une plateforme de gestion et de vente d'abonnements à des services audiovisuels dûment autorisés :
- un seul panel Filament avec permissions par rôle ;
- site public et espaces client/revendeur en Blade + Livewire + Tailwind ;
- vente directe et vente par revendeurs ;
- plusieurs devises ;
- checkout invité ;
- paiements, factures, abonnements, appareils, sessions, e-mails et thèmes.

Contraintes :
1. Ne modifie aucun fichier.
2. N'installe aucun package.
3. Ne lance aucune migration ni commande destructive.
4. N'invente pas de versions ou de fichiers absents.
5. Signale les incompatibilités Filament/Laravel éventuelles.

Retourne :
- résumé de l'état actuel ;
- versions détectées ;
- éléments réutilisables ;
- risques et dettes techniques ;
- architecture recommandée adaptée au dépôt ;
- plan de travail par phases avec fichiers concernés ;
- commandes nécessaires, sans les exécuter.

Attends mon approbation avant toute modification.
