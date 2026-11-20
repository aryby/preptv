# Prompt 14 — Portefeuille et grand livre revendeur

Implémente le portefeuille revendeur sous forme de grand livre financier traçable.

Exigences :
- un portefeuille par revendeur et devise ;
- écritures crédit/débit immuables ;
- référence unique et idempotence ;
- montant en unités mineures entières ;
- solde calculé ou maintenu de manière cohérente avec le ledger ;
- transactions DB et verrouillage `lockForUpdate` lors des opérations concurrentes ;
- interdiction de solde négatif si le modèle commercial ne l'autorise pas ;
- corrections par écritures compensatoires, jamais par modification/suppression d'une ancienne écriture ;
- lien vers commande ou opération justificative ;
- permission spécifique pour ajustement manuel avec motif obligatoire.

Ajoute des tests de concurrence, double soumission, devise, solde insuffisant et correction. Explique la stratégie de cohérence du solde.
