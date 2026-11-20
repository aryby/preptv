# Prompt 12 — Scheduler, expiration et notifications

Implémente les tâches planifiées Laravel nécessaires :
- détection des abonnements arrivant à expiration ;
- rappels configurables (par exemple J-7, J-3 et J-1) ;
- notification d'expiration ;
- mise à jour du statut expiré ;
- rapprochement des paiements en attente si l'API du prestataire le permet ;
- nettoyage prudent des données temporaires selon une politique de rétention.

Exigences :
- tâches idempotentes ;
- fuseau horaire explicite ;
- éviter les envois répétés ;
- utiliser Queue pour les e-mails et opérations longues ;
- verrouillage/`withoutOverlapping` lorsque pertinent ;
- logs structurés sans secrets ;
- tests du calendrier et des doublons.

Documente les commandes Artisan et la configuration cron requise. Ne lance aucune tâche destructive sur des données réelles.
