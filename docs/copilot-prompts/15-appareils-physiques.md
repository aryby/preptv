# Prompt 15 — Stock et appareils physiques

Implémente la gestion des appareils physiques vendus ou affectés aux clients.

Données possibles :
- numéro de série unique ;
- type, marque, modèle ;
- état du stock ;
- coût d'achat et devise ;
- commande d'achat/vente si le périmètre le prévoit ;
- propriétaire ou client ;
- date d'affectation et date de restitution ;
- historique complet des affectations.

Exigences :
- distinguer inventaire physique et appareils autorisés par un abonnement ;
- empêcher les doubles affectations incompatibles ;
- ne pas supprimer l'historique lors d'une réaffectation ;
- contrôler les accès selon rôle ;
- ajouter recherche, filtres et export si utile ;
- tests de numéro de série, statut, affectation et permissions.

N'implémente pas de fonctions de modification matérielle ou de contournement des protections des appareils.
