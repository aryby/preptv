---
applyTo: "resources/views/**/*.blade.php,resources/css/**/*.css,resources/js/**/*.js,app/Livewire/**/*.php,package.json,vite.config.js"
---

# Instructions front-end

- Les interfaces publiques, client et revendeur utilisent Blade et Livewire 4.4.8. Tailwind CSS 4 et Vite 8 sont déclarés dans `package.json`, mais les dépendances JavaScript étaient absentes au dernier contrôle. Vérifie leur installation et leurs versions avant d'utiliser leurs APIs ou de lancer un build.
- N'utilise pas Filament pour remplacer les espaces publics ou les portails client/revendeur. Réutilise layouts, composants et styles existants avant d'en créer de nouveaux.
- En Blade, échappe les données avec `{{ }}` pour prévenir XSS. Évite `{!! !!}` ; ne l'utilise que pour du contenu explicitement assaini selon une règle précise.
- Traite tout état Livewire public comme modifiable par le client. Revalide les données côté serveur, réautorise chaque action, recharge les modèles dans le périmètre de l'utilisateur et protège les identifiants sensibles contre leur modification.
- N'expose pas dans le navigateur de secrets, de données de paiement, ni de décision d'autorisation. Applique CSRF et rate limiting aux opérations sensibles.
- Les thèmes et contenus configurables sont des données, pas du code. N'évalue jamais du PHP, Blade ou JavaScript fourni par un administrateur ou un utilisateur.
- Suis la syntaxe Tailwind 4 et le pipeline Vite déjà configurés ; n'ajoute pas de dépendance sans approbation. Vérifie la présence de `node_modules` avant de lancer un build ou d'affirmer qu'il est exécutable.
- Pour un changement front-end, exécute `npm run build` seulement si les dépendances sont disponibles ; sinon rapporte précisément le blocage.
