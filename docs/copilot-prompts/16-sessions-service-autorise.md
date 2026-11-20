# Prompt 16 — Sessions et intégration à un service autorisé

Implémente uniquement la gestion applicative des droits et des sessions pour un fournisseur audiovisuel dûment autorisé.

Périmètre :
- enregistrer une référence de session fournie par l'intégration officielle ;
- associer une session à un abonnement/appareil autorisé ;
- appliquer la limite contractuelle de sessions simultanées ;
- suivre début, dernière activité et fin ;
- fermer/expirer une session selon les mécanismes documentés du fournisseur ;
- journaliser les refus et dépassements ;
- protéger les endpoints et limiter les appels.

Toute intégration de lecture doit utiliser exclusivement les API, SDK et mécanismes officiels fournis par le détenteur des droits.

INTERDICTIONS EXPLICITES :
- aucune récupération ou génération de playlists non autorisées ;
- aucune extraction de flux ou d'URL de streaming ;
- aucun contournement DRM, géoblocage ou contrôle d'accès ;
- aucune usurpation de session ou de compte ;
- aucune méthode destinée à contourner les limites imposées par le fournisseur.

Si aucune API officielle n'est disponible, construis seulement le modèle de données et une interface d'adaptation simulée pour les tests. N'invente pas d'API fournisseur.
