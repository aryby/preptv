# Prompt 07 — Catalogue, durées, prix et devises

Implémente la gestion du catalogue des offres.

Fonctionnalités :
- créer/éditer/archiver une offre ;
- description, visibilité, ordre d'affichage ;
- durées configurables (par exemple 1, 3, 6 ou 12 mois, sans les coder en dur) ;
- prix distinct par durée et devise ;
- plusieurs devises actives et une devise par défaut ;
- limites d'appareils et de sessions simultanées ;
- promotions éventuelles avec dates de validité et règles explicites.

Règles financières :
- stocker les montants en unités mineures entières ;
- ne jamais utiliser de float pour les calculs monétaires ;
- enregistrer un snapshot du nom, de la durée, du prix et de la devise dans `order_items` ;
- ne jamais modifier rétroactivement les commandes existantes lorsqu'un tarif change ;
- valider la devise et la durée côté serveur.

Crée l'interface Filament et les composants Blade/Livewire nécessaires pour afficher les offres publiées. Ajoute tests de prix, devise, disponibilité et snapshot.
