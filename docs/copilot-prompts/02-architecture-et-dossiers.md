# Prompt 02 — Architecture et organisation des dossiers

Sur la base de l'audit validé, propose puis implémente une architecture modulaire adaptée au dépôt existant.

Domaines fonctionnels :
- Identity
- Catalog
- Orders
- Payments
- Billing
- Subscriptions
- Resellers
- Devices
- Sessions
- Messaging
- Themes
- Settings
- Reporting

Organisation indicative, à adapter sans déplacer inutilement du code existant :

`app/Domain/{Domain}/Actions`
`app/Domain/{Domain}/Data`
`app/Domain/{Domain}/Enums`
`app/Domain/{Domain}/Events`
`app/Domain/{Domain}/Exceptions`
`app/Domain/{Domain}/Jobs`
`app/Domain/{Domain}/Models`
`app/Domain/{Domain}/Notifications`
`app/Domain/{Domain}/Policies`
`app/Domain/{Domain}/Services`

Les modèles peuvent rester dans `app/Models` si le projet suit déjà cette convention. Ne crée pas de couches abstraites sans besoin concret.

Règles :
- Filament Resources et composants Livewire délèguent la logique métier aux Actions/Services.
- Les opérations financières et changements d'abonnement utilisent des transactions.
- Les événements sont émis après validation de la transaction lorsque cela est nécessaire.
- Ne crée pas de microservices : l'application reste un monolithe modulaire.
- Ne crée pas de classes vides ou de dossiers sans usage.
- Ajoute un court document `docs/ARCHITECTURE.md`.

Ne modifie pas les comportements existants. Exécute les tests et rapporte les fichiers touchés.
