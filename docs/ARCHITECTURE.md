# Architecture de l’application

## État et décisions

Le dépôt est encore proche du squelette Laravel : le modèle partagé `User`, les migrations de base, une route d’accueil et le panel Filament existent, mais les domaines métier ne sont pas encore implémentés. Il n’existe actuellement aucun dossier `app/Domain`.

L’application évoluera comme un **monolithe modulaire Laravel**, sans microservices. Les domaines seront introduits avec leur première fonctionnalité réelle ; ne crée pas de dossiers ou classes vides et ne déplace pas les fichiers existants sans nécessité. Les données restent dans la base Laravel configurée — SQLite par défaut actuellement — et les requêtes ne doivent pas dépendre inutilement d’un moteur particulier.

## Couches applicatives

### Interfaces et transport

- `routes/web.php` porte les routes HTTP nommées, organisées par espace et middleware au fur et à mesure de leur création.
- Le site public et les portails client/revendeur utilisent Blade et Livewire. Ils ne deviennent pas des panels Filament.
- Le panel unique d’administration est configuré dans [AdminPanelProvider.php](../app/Providers/Filament/AdminPanelProvider.php), avec l’identifiant `admin`.
- Contrôleurs HTTP, composants Livewire et Resources/Pages/Widgets Filament restent minces : ils valident, autorisent, coordonnent et délèguent le travail métier.

### Application et domaine

Les Actions représentent des cas d’utilisation ; les Services encapsulent une logique partagée ou une intégration qui le justifie. Les dépendances sont injectées. Les classes restent dans le domaine concerné et ne forment pas une couche générique sans besoin concret.

Quand un domaine est créé, sa structure peut inclure uniquement les éléments utilisés, par exemple :

```text
app/Domain/Orders/Actions/
app/Domain/Orders/Data/
app/Domain/Orders/Enums/
app/Domain/Orders/Events/
app/Domain/Orders/Exceptions/
app/Domain/Orders/Jobs/
app/Domain/Orders/Models/
app/Domain/Orders/Services/
```

Les interactions entre domaines passent par des cas d’utilisation explicites, des événements ou des contrats stables, plutôt que par des mutations directes des modèles internes d’un autre domaine.

### Persistance et infrastructure

- Les migrations sont versionnées dans `database/migrations`; les seeders et factories restent dans les emplacements Laravel existants.
- Le modèle partagé [User.php](../app/Models/User.php) reste dans `app/Models`. Les autres modèles peuvent également y rester si cela correspond à la convention existante ; place un modèle dans un domaine seulement si cela clarifie réellement sa propriété et ses dépendances.
- Les policies suivent la convention existante sous `app/Policies` jusqu’à ce qu’un besoin métier justifie une organisation spécifique. L’autorisation s’applique côté serveur, pas seulement dans l’interface.
- Les providers enregistrent les bindings et intégrations transversales ; ils ne deviennent pas des conteneurs de logique métier.
- Les appels externes, dont les passerelles de paiement et les fournisseurs audiovisuels, sont encapsulés derrière des services adaptés et restent remplaçables/testables.

## Domaines fonctionnels

| Domaine | Responsabilité principale |
|---|---|
| Identity | Utilisateurs, authentification, rôles et permissions. `User` est actuellement partagé. |
| Catalog | Plans, offres, prix et devises affichés à l’achat. |
| Orders | Commandes directes et revendeur, checkout invité et instantané immuable du prix acheté. |
| Payments | Tentatives, paiements et adaptateurs des prestataires ; vérification des webhooks et idempotence. |
| Billing | Factures, numérotation, documents et états de règlement. |
| Subscriptions | Activation, renouvellement, suspension, expiration et résiliation. |
| Resellers | Revendeurs, clients liés, tarifs et règles de commission. |
| Devices | Appareils rattachés à un abonnement et limites associées. |
| Sessions | Sessions du service audiovisuel autorisé ; à distinguer des sessions HTTP Laravel. |
| Messaging | Notifications et e-mails transactionnels ; les modèles de contenu ne sont pas du code exécutable. |
| Themes | Présentation configurable avec valeurs validées et composants autorisés. |
| Settings | Paramètres métier explicitement typés et contrôlés. |
| Reporting | Lectures et agrégations ; pas de mutation des états métier. |

Cette liste décrit les frontières visées, pas des classes à créer immédiatement. Un domaine peut rester absent tant qu’aucune fonctionnalité ne le requiert.

## Règles des flux métier

- Les opérations financières et les transitions d’abonnement utilisent des transactions DB. Les montants ne sont pas calculés avec des flottants ; chaque montant conserve sa devise et le contexte tarifaire au moment de la commande.
- Les événements nécessitant un état durable ne sont publiés qu’après validation de la transaction.
- Un abonnement n’est jamais activé par une redirection navigateur : seule une confirmation serveur fiable, telle qu’un webhook signé traité de façon idempotente, peut confirmer le paiement.
- Les données client et revendeur sont filtrées et autorisées côté serveur pour prévenir les accès IDOR, y compris via URL directe.
- Les intégrations de contenu se limitent à des fournisseurs dûment autorisés.

## Organisation des interfaces et tests

Les Resources Filament délèguent aux Actions/Services et utilisent policies/permissions pour chaque ressource, page ou widget. Les composants Livewire réautorisent chaque action et ne font pas confiance à leur état public. Les vues Blade échappent les données ; aucun contenu administrable n’exécute du PHP ou du Blade arbitraire.

Les tests restent organisés dans `tests/Feature` et `tests/Unit`. Chaque nouveau flux ajoute des tests ciblés pour ses règles métier, permissions, cas refusés et effets de bord. Les flux de paiement testent notamment signature, idempotence, transactions et absence d’activation depuis le retour navigateur.
