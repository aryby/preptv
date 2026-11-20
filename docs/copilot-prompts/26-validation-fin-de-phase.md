# Prompt 26 — Validation obligatoire de fin de phase

Avant de considérer la phase actuelle terminée :

1. Inspecte tous les fichiers créés ou modifiés.
2. Vérifie imports, namespaces, types, conventions et compatibilité des versions.
3. Vérifie migrations, clés étrangères, index, contraintes et casts.
4. Vérifie policies, permissions, séparation client/revendeur et protection IDOR.
5. Vérifie transactions, montants, snapshots et idempotence si le domaine financier est concerné.
6. Exécute les tests ciblés et les tests pertinents du projet.
7. Exécute Laravel Pint ou l'outil de formatage configuré.
8. Vérifie qu'aucune donnée réelle ou aucun secret n'a été ajouté.
9. Mets à jour la roadmap avec le statut réel.
10. Vérifie `git diff` et résume les changements.

Retour attendu :
- fichiers créés/modifiés ;
- résumé des fonctionnalités ;
- commandes exécutées ;
- résultats exacts des tests ;
- anomalies restantes classées par priorité ;
- risques et prochaines étapes.

Ne lance pas de commande destructive. Ne marque pas la phase COMPLETED si des tests importants échouent ou n'ont pas été exécutés. Arrête-toi et attends mon approbation avant la phase suivante.
