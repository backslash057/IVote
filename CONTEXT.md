# IVote — Contexte technique du projet

> Fichier de contexte rédigé après les sessions de développement / correction.
> Objectif : documenter l'état du projet, les constats, les choix et tout ce qui a été modifié.

---

## 1. Vue d'ensemble

**IVote** = plateforme de vote en ligne avec paiement **Mobile Money** (MTN MoMo / Orange Money).
Deux volets :

- **Backend PHP** (`backend/`) : API REST + pages web admin (monolithique, PHP + MySQL).
- **App mobile Flutter** (`mobile/`) : couvre votant, espace organisateur et administration.

`backend/` et `mobile/` sont deux dépôts `git` distincts, versionnés ensemble dans `/home/starsky-ess/IVote`.

---

## 2. Environnement local (constaté)

| Élément | Valeur |
|---|---|
| Serveur backend | PHP 8.4.16 (CLI, `php -S 127.0.0.1:8080 -t backend`) |
| Base de données | MariaDB/MySQL `127.0.0.1:3306`, base `ivote` |
| Utilisateur MySQL | `backslash057` / mot de passe `root` (dev) |
| Schéma | importé depuis `backend/schema.sql` |
| Utilisateur seed | `orga@gmail.com` / `TempOrga@123` (« IVote Organizer », `user_id` 1) |
| Flutter | 3.24.0 / Dart 3.5.0 (version fournie sur la machine, rien d'autre installé) |
| Données de test | campagne id 1 « Miss UY1 Test », prix 100 FCFA/vote, 3 candidats, ~70 votes enregistrés |

---

## 3. Backend PHP — structure et API

### 3.1 Fichiers

| Fichier | Rôle |
|---|---|
| `backend/config.php` | Credentials MySQL via env : `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME` (fallbacks dev si absents) |
| `backend/router.php` | Routeur : 14 routes `/api/*` + bloc `OPTIONS` CORS (avant résolution de route) |
| `backend/utils/tokenizer.php` | JWT HS256 : émission + vérification (signature ET expiration) |
| `backend/controllers/authController.php` | Login / signup / me + extraction `Authorization: Bearer` |
| `backend/controllers/campaignController.php` | CRUD campagnes, candidats, vote, remises, retraits, dashboard |
| `backend/controllers/apiController.php` | Couche REST JSON + `respond()` avec en-têtes CORS |

### 3.2 Routes de l'API mobile (ordre du routeur)

```
GET  /api/status
GET  /api/campaigns/popular
GET  /api/campaigns                 → liste publique des campagnes
POST /api/campaigns                 → création (auth)
GET  /api/my-campaigns              → campagnes de l'utilisateur (auth)
POST /api/auth/login                → {token, user}
POST /api/auth/signup               → {token, user}
GET  /api/auth/me                   → utilisateur courant (auth)
GET  /api/campaigns/{id}/dashboard  → stats admin (auth, propriétaire)
POST /api/campaigns/{id}/votes      → vote (public, sans auth)
POST /api/campaigns/{id}/update     → édition (auth propriétaire)
POST /api/campaigns/{id}/status     → publish/close (auth propriétaire)
POST /api/campaigns/{id}/relaunch   → relancer (auth propriétaire)
POST /api/campaigns/{id}/candidates → ajout candidat (auth propriétaire)
POST /api/campaigns/{id}/payouts    → demande de retrait (auth propriétaire)
GET  /api/campaigns/{id}            → détail public (dernier dans l'ordre des routes)
```

> **Mobile sans auth** : `GET /api/campaigns/{id}` (détail public) et
> `POST /api/campaigns/{id}/votes`. Tout le reste exige `Authorization: Bearer <JWT>`.

### 3.3 Constats / corrections de sécurité

- **Faille JWT corrigée** (`backend/utils/tokenizer.php`) : le token était seulement
  décodé en base64 (aucune vérification HMAC) → un token forgé était accepté.
  Désormais : vérification **HMAC-SHA256** + **expiration** ; un token forgé
  correctement signé → `401`. Testé en direct.
- **Secrets sortis du code** : la clé JWT et les identifiants MySQL passent par des
  variables d'environnement (`IVOTE_JWT_SECRET`, `DB_*`). Des **fallbacks de dev**
  subsistent dans le code (acceptable localement, à retirer en production).
- **Fuite SQL dans `createCampaign`** : les erreurs PDO brutes allaient au client.
  Corrigé → `error_log()` + message générique. `addCandidate` capte aussi les
  `PDOException` (message ami « numéro de dossard déjà utilisé »).
- `authController` gère `REDIRECT_HTTP_AUTHORIZATION` (Apache) en plus de
  `HTTP_AUTHORIZATION`, avec repli cookie.

### 3.4 Vote (`recordVote`) — règle métier validée en direct

- Campagne doit être **active**.
- Le candidat doit **appartenir à la campagne** (sinon `422` "Ce candidat n'appartient pas à cette campagne.").
- `vote_count` : 1 à **500** (au-delà → 422).
- Méthodes acceptées : `mtn_momo`, `orange_money` (sinon 422).
- **Remises** (dégressives, identiques web ↔ mobile) :

| Votes | Remise | Multiplicateur |
|---|---|---|
| ≥ 10 | 10 % | ×0.9 |
| ≥ 20 | 15 % | ×0.85 |
| ≥ 50 | 20 % | ×0.8 |

- Montant = `vote_count × price_per_vote × multiplicateur`, arrondi.
- Réponse : `votes_added` + `amount_fcfa`.

### 3.5 Dashboard (`getDashboardData`) — revu pour des revenus réels

- `stats`: `totalVotes`, `totalRevenue`, `platformFee` (10 %), `availableBalance` (90 %).
- `candidates` : clés `candidate_id, name, candidate_number, age, theme, description,
  bio, image_url, category_id, campaign_id, category_name, total_votes,
  candidate_revenue, percentage, revenue`.
- `transactions` : `vote_id, transaction_ref, candidate_id, campaign_id,
  payment_method, vote_count, amount_fcfa, created_at, candidate_name, candidate_avatar`.
- `payoutRequests` : `payout_id, campaign_id, amount_fcfa, payment_method,
  account_holder, wallet_number, status, created_at`.

### 3.6 Candidats — ajout de catégorie (`addCandidate`)

Signatures acceptées côté API :
- `category_name` → **réutilise** la catégorie existante de la campagne si trouvée.
- `new_category_name` → **crée** une nouvelle catégorie.
- `category_id` (int) → référence directe.

Implémentation via le helper privé `createCategory(int $campaignId, string $name, bool $reuse)`.
> Ajouté pour que le mobile puisse « choisir une catégorie par nom » sans connaître son id.

---

## 4. Mobile Flutter

### 4.1 Contraintes & décisions

- **SDK `^3.5.0`** et **`flutter_lints ^4.0.0`** : rétrogradés car Dart 3.5 ne supporte
  pas les versions récentes (`flutter_lints ^5`/`^6`, `intl` récent).
- **Paiement simulé** : aucune vraie transaction. L'intégration de l'agrégateur
  **Angaraa-Pay** se fera plus tard (à ne pas implémenter).
- **Session non persistée** (choix assumé) : reconnexion à chaque lancement.
- **CI/CD absent** : pas de build Android/iOS sur cette machine (Linux).

### 4.2 Structure `mobile/lib/`

```
config.dart        kBaseUrl = String.fromEnvironment('API_BASE_URL',
                   defaultValue: 'http://127.0.0.1:8080')
                   → flutter run --dart-define=API_BASE_URL=http://IP:8080
theme.dart         AppColors (bg, surface, emerald, amber, textSecondary…)
utils/price.dart   remises PK (≥10 → 0.9, ≥20 → 0.85, ≥50 → 0.8), formatFcfa, formatCount
models/
  campaign.dart        Campaign (lit campaign_id|id, totalVotes|total_votes,
                       isActive / isDraft / isScheduled / isEnded)
  campaign_detail.dart CampaignDetail + Category + Candidate
  dashboard.dart       DashboardData + AdminCandidate + TransactionRecord + PayoutRequest
  user.dart            AppUser + VoteRecord
services/
  api_client.dart     GET/POST JSON, ApiException{message,statusCode},
                      ajoute auto 'Authorization: Bearer {Session.token}'
  auth_service.dart   login/signup (lit token + user en racine de réponse), logout, restoreSession
  campaign_service.dart fetchCampaigns, fetchMyCampaigns, dashboard, createCampaign,
                      setStatus, relaunch, addCandidate (…category_name/new_category_name),
                      createPayout, vote, fetchCampaignDetail
  session.dart        Session.token / Session.user (mémoire)
screens/
  home_page.dart            liste publique + accès espace organisateur (menu)
  campaign_detail_page.dart détail campagne + vote (public)
  login_page.dart / signup_page.dart
  organizer_home_page.dart  mes campagnes (login requis)
  create_campaign_page.dart
  campaign_admin_page.dart  stats/transactions/retraits/candidats + dialogues
widgets/
  campaign_card.dart   (nettoyé : InkWell fermé, onTap → détail)
  hero_section.dart    (utilise .withOpacity, compatible Flutter 3.24)
  empty_state.dart
  vote_sheet.dart      form → popup USSD simulée → confirm ; showVoteSheet(...)
                       retourne true quand le flux est terminé
```

- **Entrée** : `main.dart` → `MyApp` (MaterialApp dark, colorScheme emerald/amber) → `HomePage`.
- **Ancien fichier supprimé** : `lib/services/campaign_services.dart` (remplacé par `campaign_service.dart`).

### 4.3 Compatibilité Flutter 3.24 (constats corrigés)

- `showDatePicker` : le paramètre `backgroundColor` **n'existe pas** dans Flutter 3.24
  (ajouté dans des versions ultérieures) → retiré, thème utilisé à la place.
- Les champs de saisie de `DropdownButtonFormField` utilisent **`value:`**
  (et non `initialValue:`, paramètre des versions plus récentes).
- Motifs de la boucle UI : `(BuildContext → …)` requiert des gardes `mounted`
  avant réutilisation du `context` après un `await` (lint `use_build_context_synchronously`).

### 4.4 Correspondance mobile ↔ API (vérifiée)

- `auth_service` lit `result['token']` / `result['user']` **en racine** — conforme au
  format réellement renvoyé par `apiLogin`/`apiSignup` (`{success, token, user}`).
- `local`/`DashboardData.fromJson` correspond clé pour clé au JSON de
  `getDashboardData` (`stats`, `candidates`, `transactions`, `payoutRequests`).
- Modèle `Campaign` tolérant : accepte `campaign_id` ou `id`, `totalVotes` ou `total_votes`.

---

## 5. Vérifications effectuées (résultats réels)

| Vérification | Résultat |
|---|---|
| `php -l backend/controllers/campaignController.php` | OK |
| `flutter pub get` | OK (26 deps résolues pour Dart 3.5) |
| `flutter analyze` | **0 issue** |
| `flutter test` (smoke test remplacé) | **1 passed** (`test/widget_test.dart`) |
| `flutter build web` | **Built build/web** (~115 s) |
| `curl` login → token JWT | OK |
| `GET /api/my-campaigns` | OK (objet avec `campaign_id`, `totalVotes`, `status`…) |
| `POST /api/campaigns/1/candidates` `{category_name:"Miss"}` | OK (catégorie réutilisée) |
| `POST …/candidates` `{new_category_name:"Mr"}` | OK (catégorie créée) |
| `GET /api/campaigns/1` | OK (détail public+`computed_status`) |
| `GET /api/campaigns/1/dashboard` | OK (clés conformes au modèle Dart) |

---

## 6. Comment lancer un test local

```bash
# 1) Base de données
mysql -h 127.0.0.1 -u backslash057 -proot ivote < backend/schema.sql   # 1re fois

# 2) API (depuis la racine du projet)
php -S 127.0.0.1:8080 -t backend            # router.php fait le routage
#   ou avec secrets :
#   IVOTE_JWT_SECRET=... DB_USER=... DB_PASS=... php -S 127.0.0.1:8080 -t backend

# 3) App mobile
cd mobile
flutter pub get
flutter analyze
flutter test
flutter run --dart-define=API_BASE_URL=http://<IP_MACHINE>:8080
```

---

## 7. Points d'attention restants (recommandations)

1. **`updateCampaign` (web)** : expose encore des erreurs PDO/fichiers brutes dans le
   parcours web admin (l'API `/data`/`/update` est OK). À nettoyer de la même façon que `createCampaign`.
2. **Fallbacks dev en dur** dans `config.php` et `tokenizer.php` : à neutraliser en production
   (exiger les variables d'environnement).
3. `.env` : un fichier de secrets éventuel **ne doit jamais être committé** — le vérifier
   dans `.gitignore`.
4. **Intégration Angaraa-Pay** : remplacer la simulation de paiement (USSD factice) par
   l'appel réel quand l'API sera disponible.
5. **Persistance de session mobile** : actuellement en mémoire ; à brancher sur
   `shared_preferences`/`flutter_secure_storage` si souhaité.
6. **Snapshot Flutter** : la machine impose Flutter 3.24 / Dart 3.5 → conserver les
   contraintes de dépendances basses (`^3.5.0`, `flutter_lints ^4.0.0`).