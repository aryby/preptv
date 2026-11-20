# GitHub Copilot — Prompts de développement

Ce dossier contient les prompts à exécuter progressivement dans **GitHub Copilot Chat en mode Agent** pour construire une plateforme de vente et de gestion d'abonnements à des services audiovisuels dûment autorisés.

## Recommandation d'utilisation

1. Ouvrir le dépôt Laravel dans VS Code.
2. Installer/activer **GitHub Copilot** et **GitHub Copilot Chat**.
3. Ouvrir Copilot Chat et sélectionner **Agent Mode**.
4. Commencer par `00-audit-du-depot.md`. Ne pas sauter l'audit.
5. Exécuter un seul prompt à la fois, lire le plan proposé et examiner les changements.
6. Après chaque phase, lancer les tests et vérifier le diff Git.
7. Faire un commit Git après chaque phase validée.
8. Ne jamais lancer `migrate:fresh`, supprimer des données, déployer ou modifier des secrets sans autorisation explicite.

## Ordre recommandé

| Ordre | Fichier | Objectif |
|---:|---|---|
| 00 | `00-audit-du-depot.md` | Audit sans modification |
| 01 | `01-instructions-copilot.md` | Instructions persistantes du dépôt |
| 02 | `02-architecture-et-dossiers.md` | Architecture modulaire |
| 03 | `03-migrations.md` | Schéma de base de données |
| 04 | `04-modeles-eloquent.md` | Modèles et relations |
| 05 | `05-authentification-roles-permissions.md` | Authentification, rôles et permissions |
| 06 | `06-panel-filament.md` | Panel d'administration unique |
| 07 | `07-catalogue-et-tarification.md` | Offres, durées et devises |
| 08 | `08-commandes-checkout-invite.md` | Commandes et checkout invité |
| 09 | `09-paiements-webhooks.md` | Paiements et webhooks |
| 10 | `10-facturation-pdf.md` | Factures et PDF |
| 11 | `11-cycle-de-vie-abonnements.md` | Activation et renouvellement |
| 12 | `12-taches-planifiees.md` | Expiration et rappels |
| 13 | `13-espace-revendeur.md` | Revendeurs et clients |
| 14 | `14-portefeuille-revendeur.md` | Portefeuille et grand livre |
| 15 | `15-appareils-physiques.md` | Stock et affectation des appareils |
| 16 | `16-sessions-service-autorise.md` | Limites de sessions côté application |
| 17 | `17-gestion-templates-email.md` | Templates d'e-mails |
| 18 | `18-gestion-themes.md` | Thèmes prédéfinis |
| 19 | `19-site-public.md` | Site public Blade/Livewire |
| 20 | `20-portail-client.md` | Espace client |
| 21 | `21-revue-code.md` | Revue technique |
| 22 | `22-tests-automatises.md` | Tests automatisés |
| 23 | `23-audit-securite.md` | Audit de sécurité |
| 24 | `24-deploiement-production.md` | Préparation du déploiement |
| 25 | `25-documentation-roadmap.md` | Documentation et feuille de route |
| 26 | `26-validation-fin-de-phase.md` | Contrôle à la fin de chaque phase |

## Stack cible

- Laravel 13
- PHP 8.3+
- Filament v4+ — un seul panel d'administration
- Blade + Livewire + Tailwind CSS pour les interfaces publiques et les portails
- Sqlite
- Laravel Fortify pour l'authentification si nécessaire
- Filament Shield pour les rôles et permissions
- Spatie Activitylog pour l'audit
- Spatie Media Library pour les médias, si utile
- DomPDF pour les factures PDF
- Laravel Queue et Scheduler
- Redis facultatif, à ajouter lorsque l'infrastructure le justifie

**Important :** les versions et compatibilités doivent être vérifiées dans le dépôt et dans la documentation officielle des packages avant toute installation ou mise à niveau.

## Décisions fonctionnelles retenues

- Un seul panel Filament, avec autorisations par rôle.
- Frontend en Blade + Livewire + Tailwind.
- Vente directe et vente par revendeurs.
- Plusieurs devises.
- Abonnements à durée déterminée, tarifs configurables par durée et devise.
- Checkout invité possible.
- Gestion des factures, paiements, utilisateurs, appareils, sessions, e-mails et thèmes.
- Toute activation d'abonnement doit être déclenchée par une confirmation de paiement vérifiée côté serveur.

## Règle de conformité

La plateforme ne doit vendre et gérer que des abonnements et contenus pour lesquels l'exploitant possède les droits ou une autorisation contractuelle. Les intégrations de lecture doivent utiliser les API et mécanismes officiels du fournisseur autorisé. Ne pas implémenter de récupération de playlists, extraction de flux, contournement DRM ou contournement de contrôles d'accès.

## Prompt de clôture

À la fin de chaque phase, exécuter `26-validation-fin-de-phase.md`. Ne passer à la phase suivante que lorsque les anomalies bloquantes sont résolues ou explicitement documentées.
