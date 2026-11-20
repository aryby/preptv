# Prompt 20 — Espace client

Construis l'espace client en Blade/Livewire avec authentification et policies.

Fonctionnalités :
- tableau de bord ;
- profil et coordonnées ;
- commandes et paiements ;
- factures téléchargeables ;
- abonnements et dates de validité ;
- appareils associés ;
- historique pertinent ;
- accès aux notifications et demandes de support si prévu.

Sécurité :
- un client ne consulte que ses propres ressources ;
- ne jamais faire confiance à un `user_id` fourni par le navigateur ;
- protéger les téléchargements par policy ou URL signée à durée limitée ;
- validation serveur sur toutes les actions ;
- rate limiting pour les actions sensibles ;
- tests d'accès direct par ID et de séparation entre deux clients.

Conserve une interface simple, accessible et adaptée au mobile.
