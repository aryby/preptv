# Prompt 17 — Gestion des templates e-mail

Implémente un gestionnaire de templates e-mail multilingues.

Fonctionnalités :
- nom, slug, langue, sujet, HTML et texte ;
- activation/désactivation ;
- aperçu avec données de démonstration ;
- envoi de test réservé aux administrateurs autorisés ;
- journal des envois et erreurs ;
- variables autorisées documentées : customer_name, order_number, plan_name, subscription_end, invoice_url, etc.

Sécurité :
- liste blanche des variables ;
- échapper les données utilisateur ;
- ne jamais évaluer du PHP, Blade arbitraire ou du JavaScript fourni par un administrateur ;
- protéger les URLs signées ;
- ne pas exposer de secrets dans les logs ;
- envoyer les e-mails via Queue lorsque pertinent.

Ajoute tests de rendu, variables inconnues, XSS, permissions et journalisation.
