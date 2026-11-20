# Prompt 06 — Panel Filament unique

Construis le panel d'administration unique avec Filament v4, en respectant les versions réellement installées.

Organise les Resources par groupes :
- Catalogue ;
- Ventes ;
- Paiements et facturation ;
- Abonnements ;
- Revendeurs ;
- Appareils et sessions ;
- Communication ;
- Apparence et paramètres ;
- Administration et audit.

Resources possibles : PlanResource, PlanPriceResource, OrderResource, PaymentResource, InvoiceResource, SubscriptionResource, ResellerResource, ResellerPackageResource, WalletTransactionResource, DeviceResource, EmailTemplateResource, ThemeResource, UserResource.

Crée des Widgets pertinents : chiffre d'affaires, commandes récentes, abonnements actifs/expirant, paiements en attente, activité revendeur.

Règles :
- respecter les API Filament v4, ne pas copier du code Filament v3 sans adaptation ;
- Resources minces, logique métier dans les Actions/Services ;
- appliquer policies et Shield ;
- utiliser des filtres, recherches, eager loading et pagination ;
- protéger les actions destructives par confirmation et autorisation ;
- ne pas exposer de secrets ou données de paiement sensibles ;
- vérifier l'accès selon chaque rôle.

Commence par un plan et une liste des Resources. Implémente progressivement et ajoute des tests d'autorisation.
