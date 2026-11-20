# Prompt 09 — Paiements, adaptateurs et webhooks

Conçois une couche de paiement extensible avec une interface commune et des adaptateurs pour les prestataires réellement choisis.

Ne suppose pas qu'un fournisseur est configuré. Commence par inventorier les prestataires déjà utilisés et proposer l'interface.

Exigences :
- création d'une tentative de paiement liée à une commande ;
- montant et devise issus de la commande côté serveur ;
- vérification cryptographique de la signature webhook selon la documentation officielle du prestataire ;
- idempotence par identifiant d'événement et référence transactionnelle ;
- stockage d'un journal d'événements webhook sans données sensibles inutiles ;
- validation du montant, de la devise, du statut et de la commande ;
- gestion des échecs, annulations, remboursements et paiements en attente ;
- journalisation et rapprochement des paiements ;
- aucune donnée de carte bancaire/CVV stockée par l'application ;
- secrets dans `.env` ou gestionnaire de secrets, jamais dans Git.

Ne jamais marquer un paiement comme réussi sur la seule base d'une redirection navigateur. Ne pas activer l'abonnement dans cette phase. Les webhooks doivent être testés avec des signatures valides, invalides, dupliquées et rejouées.
