# Prompt 05 — Authentification, rôles et permissions

Configure l'authentification en utilisant les mécanismes déjà présents ou Laravel Fortify si son installation est validée.

Rôles cibles :
- super_admin ;
- admin ;
- support ;
- finance ;
- reseller ;
- customer.

Utilise Filament Shield compatible avec la version Filament installée, après vérification de la compatibilité et de la configuration.

Exigences :
- un seul panel Filament ;
- accès au panel limité aux rôles autorisés ;
- permissions granulaires sur les Resources, Pages et Widgets ;
- policies pour les commandes, factures, abonnements, appareils et données revendeur ;
- un revendeur ne voit que ses clients, commandes et transactions ;
- un client ne voit que ses propres données ;
- support et finance ont des droits limités selon leur fonction ;
- protection du dernier super_admin contre une rétrogradation accidentelle ;
- aucun rôle privilégié attribué depuis une inscription publique ;
- validation et tests de toutes les règles d'accès.

Ne te contente pas de masquer les boutons dans l'interface : applique les policies côté serveur. Ajoute des tests contre les accès directs par URL et les IDOR.
