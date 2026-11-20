# Prompt 21 — Revue de code technique

Effectue une revue du code modifié depuis le dernier point de validation.

Vérifie :
- compatibilité Laravel/PHP/Filament avec les versions du dépôt ;
- conventions et architecture ;
- imports, namespaces, types et nullabilité ;
- relations et requêtes Eloquent ;
- N+1, pagination et index ;
- validation, policies et permissions ;
- transactions et cohérence financière ;
- idempotence des paiements et abonnements ;
- XSS, CSRF, IDOR, mass assignment et injection ;
- logs et exposition de données sensibles ;
- accessibilité et responsive ;
- tests manquants.

Classe chaque problème : CRITIQUE, ÉLEVÉ, MOYEN, FAIBLE.
Pour chaque problème, indique le fichier, la conséquence et une correction proposée.

Corrige uniquement les problèmes clairement dans le périmètre validé. Pour une refonte importante, présente le plan et attends mon accord. N'affirme jamais qu'un test passe sans l'avoir exécuté.
