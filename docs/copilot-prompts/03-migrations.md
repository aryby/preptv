# Prompt 03 — Migrations et schéma relationnel

Conçois les migrations nécessaires après avoir inspecté les migrations existantes. Évite les doublons et les changements destructifs.

Tables fonctionnelles à couvrir, selon les besoins validés :
- currencies ;
- plans, plan_prices ;
- customer_profiles si les informations client ne sont pas portées par users ;
- orders, order_items ;
- payments, payment_webhook_events ;
- invoices, invoice_items, credit_notes si nécessaire ;
- subscriptions, subscription_histories ;
- reseller_profiles, reseller_packages, reseller_wallets, wallet_transactions, reseller_customers ;
- devices, device_assignments ou subscription_devices avec historique ;
- streaming_sessions ou session_audit_logs ;
- email_templates, email_logs ;
- themes, theme_settings ;
- settings si une solution existante n'est pas déjà utilisée.

Exigences :
1. Définir précisément les clés étrangères, index, contraintes uniques et règles de suppression.
2. Préserver les historiques financiers et d'audit : ne pas permettre la suppression en cascade des factures, paiements, écritures de portefeuille ou historiques importants.
3. Préférer l'archivage/soft delete lorsque pertinent.
4. Stocker les montants en unités mineures entières (`amount_minor`) et conserver le code devise ISO dans chaque document financier.
5. Enregistrer des snapshots des noms, prix, taxes, devise et coordonnées de facturation au moment de la commande/facture.
6. Prévoir les clés d'idempotence pour paiements et événements webhook.
7. Séparer la limite d'appareils autorisés de la limite de sessions simultanées.
8. Ajouter des index pour les recherches fréquentes et les tâches planifiées.
9. Définir une politique claire pour les commandes invitées (`user_id` nullable) sans perdre l'identité client historique.
10. Utiliser des statuts cohérents, idéalement des enums PHP côté application.

Avant le code, présente un diagramme ERD Mermaid et explique les relations. Après validation, crée les migrations et tests de contraintes.

Ne lance jamais `migrate:fresh`, `db:wipe` ou une commande supprimant les données. Ne lance pas les migrations sur une base distante ou de production.
