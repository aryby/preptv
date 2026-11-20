# Prompt 13 — Gestion des revendeurs

Implémente la gestion des revendeurs et leur espace Blade/Livewire protégé.

Fonctionnalités :
- création/validation/suspension d'un profil revendeur ;
- code revendeur unique ;
- packs ou tarifs revendeur configurables ;
- création de commandes pour un client ;
- association du client au revendeur ;
- consultation de ses propres clients, commandes, abonnements et factures autorisées ;
- règles de commission ou marge documentées ;
- tableau de bord revendeur.

Sécurité :
- isolation stricte par reseller_profile_id ;
- un revendeur ne peut jamais changer l'identifiant d'un autre revendeur pour accéder à ses données ;
- toutes les requêtes et actions serveur appliquent les policies ;
- aucune attribution de rôle revendeur depuis une requête publique ;
- tests d'accès croisé entre deux revendeurs.

Ne mets pas en place de solde ou de mouvement financier dans cette phase ; prépare uniquement les contrats nécessaires.
