# Prompt 10 — Factures et PDF

Implémente la facturation à partir des données snapshot de la commande et du paiement confirmé.

Fonctionnalités :
- génération d'un numéro de facture unique selon une règle configurable ;
- identité vendeur et coordonnées de facturation ;
- identité client snapshot ;
- lignes, quantité, prix unitaire, taxes, total et devise ;
- génération PDF avec DomPDF si sa compatibilité est confirmée ;
- téléchargement autorisé par policy ou lien signé à durée limitée ;
- envoi par e-mail ;
- conservation d'une copie cohérente de la facture émise.

Règles :
- une facture émise ne se modifie pas silencieusement ;
- correction via avoir/credit note ou document correctif selon les règles applicables ;
- ne jamais recalculer une ancienne facture à partir des tarifs actuels ;
- ne pas supposer un taux de TVA : rendre les règles fiscales configurables et faire valider les obligations par un professionnel compétent ;
- tests de numérotation, devise, totaux, accès et rendu PDF.

Présente d'abord le modèle de données et le flux, puis implémente.
