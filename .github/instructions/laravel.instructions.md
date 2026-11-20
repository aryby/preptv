---
applyTo: "app/**/*.php,bootstrap/**/*.php,config/**/*.php,database/**/*.php,routes/**/*.php"
---

# Instructions Laravel

- La cible est Laravel 13+ et PHP 8.3+. Le socle vérifié est Laravel 13.43.0 et PHP 8.4.0, avec `^13.17` et `^8.3` dans Composer. Reconfirme les versions installées avant d'utiliser une API dépendante de la version.
- Suis les conventions Laravel et les patterns déjà présents dans le dépôt. Réutilise les classes existantes avant d'ajouter une abstraction ou une nouvelle couche.
- Organise le métier en monolithe modulaire. Ajoute des Actions, Services, DTO/Data ou Enums dans `app/Domain/{Domain}` seulement lorsqu'ils servent un cas concret ; ne crée pas de classes ou dossiers vides.
- Garde les contrôleurs minces. Valide les requêtes avec Form Requests lorsque cela clarifie les règles et autorise les opérations avec des policies. Injecte les dépendances au lieu d'instancier les services dans le code métier.
- Respecte PSR-12 et le style Laravel existant ; utilise Laravel Pint pour formater le PHP modifié.
- Ne transmets jamais `$request->all()` directement à un modèle. Utilise des données validées et des attributs explicitement assignables. Vérifie l'autorisation et le périmètre de chaque ressource pour prévenir les IDOR.
- Échappe les sorties HTML et assainis strictement tout HTML riche accepté pour prévenir XSS.
- Utilise des Enums PHP pour les ensembles finis de statuts. Modélise explicitement les transitions autorisées plutôt que de modifier un statut sans garde métier.
- Encapsule les écritures financières et transitions d'abonnement dans des transactions DB. Ajoute verrouillage ou contrôle de concurrence lorsqu'une même ressource peut être débitée ou modifiée simultanément. Conserve les montants dans une représentation exacte et la devise avec l'opération.
- Émets les événements après validation de la transaction lorsque les consommateurs ne doivent voir que des changements confirmés.
- Les intégrations de paiement doivent vérifier côté serveur les webhooks signés et idempotents. N'active jamais un abonnement depuis le seul retour navigateur.
- Ne journalise pas de secrets, données de carte ou jetons. N'intègre que des fournisseurs de contenus audiovisuels autorisés.
- Enregistre middleware et providers selon les conventions de la version Laravel installée ; vérifie les fichiers de démarrage et le routage existants avant de les modifier.
