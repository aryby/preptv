# Prompt 18 — Gestion des thèmes

Implémente un Theme Manager simple basé sur des thèmes prédéfinis et contrôlés par le code.

Fonctionnalités :
- sélectionner le thème actif ;
- personnaliser couleurs, logo, favicon, typographie et paramètres autorisés ;
- aperçu ;
- valeurs par défaut et validation ;
- stockage des médias via le mécanisme existant ou Spatie Media Library si installé.

Sécurité et maintenance :
- ne pas permettre l'upload ou l'exécution de fichiers PHP/Blade ;
- ne pas permettre l'injection de JavaScript arbitraire ;
- ne pas construire dynamiquement des vues à partir de contenu non fiable ;
- valider les valeurs CSS/URL ;
- conserver un fallback si le thème actif est incomplet.

Crée au moins un thème propre et responsive, puis documente comment ajouter un nouveau thème par le code.
