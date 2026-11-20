---
applyTo: "tests/**/*.php,phpunit.xml"
---

# Instructions de tests

- Le dépôt utilise actuellement PHPUnit 12.5.45 ; écris des tests PHPUnit en suivant les exemples et helpers du projet. N'introduis pas Pest sans décision explicite.
- Privilégie les tests Feature pour routes, autorisations, flux Livewire/Filament et intégrations ; utilise les tests Unit pour la logique isolée.
- Ajoute des tests ciblés pour le comportement modifié : accès autorisé et refusé, accès direct par URL, séparation des données client/revendeur, validation et transitions d'état.
- Pour les paiements, couvre au minimum signature webhook, idempotence, traitement des erreurs et absence d'activation sur une simple redirection navigateur. Simule les services externes plutôt que d'appeler un fournisseur réel.
- Utilise factories et bases isolées/in-memory. Ne connecte jamais les tests à une base partagée ou de production et ne lance pas de commande destructive pour préparer les tests.
- Vérifie les invariants financiers et l'atomicité des transactions sans dépendre de détails d'implémentation. Ne mets pas de vrais secrets ni données de carte dans les fixtures.
- Lance le test le plus ciblé, par exemple `php artisan test --compact tests\Feature\ExampleTest.php`, puis élargis si nécessaire. Pour du PHP modifié, lance `vendor\bin\pint --dirty --format agent`.
- Pour une modification front-end, lance `npm run build` si les dépendances front-end sont installées. Rapporte les commandes exécutées et leurs résultats exacts ; ne masque pas un test non exécuté ou échoué.
