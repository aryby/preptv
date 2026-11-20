# Prompt 11 — Cycle de vie des abonnements

Implémente le domaine Subscription.

Statuts à définir clairement : pending, active, expired, suspended, cancelled (à adapter au besoin métier).

Règles :
- un abonnement est créé/activé uniquement après confirmation serveur d'un paiement valide ;
- le traitement doit être idempotent : un même événement de paiement ne crée pas plusieurs abonnements ;
- `starts_at` et `ends_at` sont calculés selon une règle explicite ;
- un renouvellement avant expiration prolonge à partir de la date de fin actuelle ;
- un renouvellement après expiration démarre à partir de la date de confirmation du paiement ;
- toute transition importante est enregistrée dans subscription_histories ;
- suspension, annulation et réactivation suivent des règles contrôlées ;
- une modification manuelle exige permission et justification ;
- les commandes et factures historiques restent intactes.

Ne développe aucun mécanisme de lecture non autorisé. L'abonnement représente un droit commercial géré par l'application.

Ajoute des tests couvrant paiement confirmé, paiement invalide, événement dupliqué, renouvellement avant/après expiration, annulation et historique.
