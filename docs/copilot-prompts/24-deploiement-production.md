# Prompt 24 — Préparation du déploiement production

Prépare une procédure de déploiement adaptée à l'infrastructure détectée (Nginx/Apache, PHP-FPM, Sqlite, queue, scheduler).

Documente :
- versions PHP/extensions et configuration ;
- variables d'environnement nécessaires, sans valeurs secrètes ;
- build des assets ;
- migrations en mode production ;
- cache config/routes/views ;
- workers de queue et redémarrage contrôlé ;
- cron Laravel Scheduler ;
- stockage et permissions ;
- HTTPS, cookies sécurisés et headers ;
- sauvegarde DB et fichiers ;
- health checks, logs et monitoring ;
- rollback applicatif et procédure de restauration.

Contraintes :
- ne déploie rien ;
- ne te connecte pas à un serveur ;
- ne demande ni n'affiche de mot de passe, clé privée ou token ;
- ne lance aucune commande destructive ;
- ne propose pas `migrate:fresh` ;
- signale les opérations nécessitant une fenêtre de maintenance.

Crée `docs/DEPLOYMENT.md` et une checklist de mise en production.
