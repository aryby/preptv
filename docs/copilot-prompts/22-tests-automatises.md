# Prompt 22 — Tests automatisés

Ajoute ou complète les tests avec le framework déjà configuré (Pest ou PHPUnit).

Couvre au minimum :
- catalogue, durée, devise et snapshots de prix ;
- checkout invité et utilisateur connecté ;
- montant recalculé côté serveur ;
- paiement confirmé, invalide, dupliqué et webhook rejoué ;
- facture et devise ;
- activation idempotente ;
- renouvellement avant/après expiration ;
- suspension et annulation ;
- rappels d'expiration sans doublons ;
- séparation des données client ;
- séparation des données entre revendeurs ;
- portefeuille, concurrence et solde négatif ;
- limites d'appareils et de sessions ;
- permissions Filament et accès direct par URL.

Utilise des factories et des mocks pour les prestataires externes. N'appelle aucun vrai service de paiement dans les tests.

Exécute les tests ciblés puis la suite pertinente. Rapporte les commandes exactes, le nombre de tests réussis/échoués et les éventuelles limites. Ne masque pas un test échoué.
