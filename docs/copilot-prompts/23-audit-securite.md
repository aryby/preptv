# Prompt 23 — Audit de sécurité

Réalise un audit sécurité ciblé de l'application.

Contrôle :
- authentification, sessions et réinitialisation du mot de passe ;
- autorisations serveur, policies, Shield et IDOR ;
- validation et mass assignment ;
- XSS, CSRF, injection SQL et upload de fichiers ;
- rate limiting et protection anti-abus ;
- sécurité des liens de commande/facture ;
- signatures webhook, rejeu et idempotence ;
- concurrence sur commandes, paiements et portefeuille ;
- séparation client/revendeur ;
- exposition des logs, erreurs et données personnelles ;
- configuration CORS, cookies, HTTPS et headers ;
- secrets et fichiers `.env` ;
- dépendances vulnérables.

N'effectue aucun test intrusif sur un système tiers ou en production. N'exfiltre aucune donnée.

Fournis un rapport priorisé avec preuves dans le code, impact, correction et tests de non-régression. Corrige les problèmes simples dans le périmètre validé et demande confirmation avant tout changement architectural.
