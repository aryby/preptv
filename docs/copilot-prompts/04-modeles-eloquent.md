# Prompt 04 — Modèles Eloquent

À partir des migrations réellement présentes, crée ou complète les modèles Eloquent.

Pour chaque modèle :
- `$fillable` ou `$guarded` cohérent avec la sécurité ;
- casts précis (enum, boolean, array/json, dates, montants) ;
- relations belongsTo/hasMany/belongsToMany ;
- scopes utiles ;
- factories et seeders de développement si pertinents ;
- méthodes métier simples uniquement si elles appartiennent naturellement au modèle.

Modèles possibles : Currency, Plan, PlanPrice, Order, OrderItem, Payment, PaymentWebhookEvent, Invoice, InvoiceItem, Subscription, SubscriptionHistory, ResellerProfile, ResellerPackage, ResellerWallet, WalletTransaction, ResellerCustomer, Device, DeviceAssignment, StreamingSession, EmailTemplate, EmailLog, Theme, ThemeSetting.

Points de vigilance :
- ne jamais recalculer une ancienne commande avec le prix actuel du catalogue ;
- ne pas exposer les champs sensibles via `$fillable` ;
- conserver les historiques d'affectation et de statut ;
- éviter les relations qui autorisent un client ou revendeur à accéder aux données d'un autre ;
- ne pas introduire de N+1 dans les relations courantes.

Ajoute des tests unitaires/fonctionnels pour les relations importantes. N'invente pas de colonnes : aligne les modèles sur les migrations.
