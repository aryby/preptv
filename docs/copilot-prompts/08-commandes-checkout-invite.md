# Prompt 08 — Commandes et checkout invité

Implémente le panier/checkout et le cycle de commande, sans activer d'abonnement dans cette phase.

Scénario :
1. Un visiteur choisit une offre et une durée.
2. Il saisit les informations minimales de facturation et son e-mail.
3. Le serveur recalcule le prix à partir du catalogue et valide la devise.
4. Une commande est créée avec un numéro unique et des snapshots immuables des lignes.
5. La commande passe dans un statut en attente de paiement.
6. Le client reçoit une confirmation de commande ou une instruction de paiement.

Exigences :
- checkout invité et client connecté ;
- validation serveur, rate limiting et protection anti-abus ;
- prix jamais acceptés depuis le navigateur sans recalcul ;
- transaction DB pour création de commande et lignes ;
- identifiant de commande non séquentiel ou suffisamment imprévisible pour les liens publics ;
- accès public à une commande via un jeton signé/secret, jamais par simple ID ;
- gestion des commandes abandonnées/expirées ;
- tests des cas valides et invalides.

Important : ne crée pas et n'active pas de Subscription dans cette phase. Attends le paiement vérifié dans une phase ultérieure.
