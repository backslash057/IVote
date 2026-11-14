# IVote

IVote est une plateforme de vote **monétisé** (paiement Mobile Money) disponible sur **web** et **mobile**.

---

## 📌 Description du projet

**IVote** permet de créer des campagnes / concours de vote payants, présenter des candidats, collecter des votes (MTN MoMo / Orange Money) et gérer les retraits de revenus. Une seule interface est développée en **Flutter** pour le **web et le mobile** ; un **backend PHP** fournit l'API REST.

> Paiement **simulé** pour l'instant : intégration de l'agrégateur **Angaraa-Pay** prévue plus tard.

---

## 🏗️ Architecture

```
IVote/
├── backend/                         # API REST PHP 8.4 + MySQL
│   ├── config.php                   # Credentials BDD via env (DB_*)
│   ├── bootstrap.php                # Chargement .env + échec ferme
│   ├── router.php                   # Routeur (web + API /api/*, CORS)
│   ├── .env.example                 # Modèle de configuration
│   ├── schema.sql                   # Schéma + seed super admin (production)
│   ├── demo_seed.sql                # Données de démo locales (autoportant)
│   ├── migrations/                  # Migrations SQL versionnées
│   ├── controllers/
│   │   ├── BaseController.php       # Helpers (JSON, CORS, uploads, clientIp)
│   │   ├── AuthController.php       # login / signup / me (JWT)
│   │   ├── CampaignController.php   # campagnes, candidats, votes, retraits
│   │   └── AdminController.php      # administration (super admin)
│   ├── routes/                      # Pages web PHP (admin)
│   ├── public/                      # Assets statiques
│   ├── uploads/                     # Images candidates / campagnes
│   ├── utils/tokenizer.php          # JWT HS256 (signature + expiration)
│   └── deploy/                      # Déploiement VPS
│       ├── setup.sh                 # Installation initiale (sans sudo)
│       ├── deploy.sh                # Mise à jour (git pull + migrations)
│       ├── nginx.conf               # Template reverse proxy (admin)
│       ├── ivote.service            # Template service systemd (admin)
│       └── DEPLOYMENT.md            # Feuille de route de déploiement
└── mobile/                          # App Flutter 3.24 (web + mobile)
    ├── lib/
    │   ├── main.dart                # Entrée MyApp → HomePage
    │   ├── config.dart              # kBaseUrl (--dart-define API_BASE_URL)
    │   ├── theme.dart               # AppColors (dark, emerald/amber)
    │   ├── models/                  # campaign, campaign_detail, dashboard, user
    │   ├── services/                # api_client, auth_service, campaign_service, session
    │   ├── screens/                 # home, campagne, admin, login/signup, création
    │   ├── utils/price.dart         # remises dégressives + formats
    │   └── widgets/                 # campaign_card, vote_sheet, empty_state…
    ├── test/widget_test.dart        # Smoke test
    └── pubspec.yaml                 # SDK Dart ^3.5.0
```

- **Backend** : PHP natif + MySQL, JWT HS256, CORS.
- **Frontend web + mobile** : Flutter (une seule base de code).

---

## 🔑 Configuration

Le backend lit **toutes** ses secrets dans les variables d'environnement,
avec repli sur le fichier `backend/.env` (jamais versionné).

```bash
cp backend/.env.example backend/.env
# puis renseigner DB_* et IVOTE_JWT_SECRET
```

| Variable | Obligatoire | Description |
|---|---|---|
| `DB_HOST` | non | défaut `127.0.0.1` |
| `DB_PORT` | non | défaut `3306` |
| `DB_NAME` | non | défaut `ivote` |
| `DB_USER` | **oui** | utilisateur MySQL dédié |
| `DB_PASS` | **oui** | mot de passe de cet utilisateur |
| `IVOTE_JWT_SECRET` | **oui** | `openssl rand -hex 48` |
| `IVOTE_ALLOWED_ORIGINS` | non | origines CORS du front web, séparées par `,` |
| `IVOTE_TRUST_PROXY` | non | `1` pour accepter `X-Real-IP`/`X-Forwarded-For` (reverse proxy) |
| `API_BASE_URL` (Flutter) | non | défaut `http://127.0.0.1:8080` |

> **Échec ferme** : si `DB_USER`, `DB_PASS` ou `IVOTE_JWT_SECRET` manque,
> le backend refuse de démarrer et répond 500. Aucun secret n'est plus
> codé en dur dans le dépôt.

**Seed de production (`schema.sql`)** : super admin `admin@ivote.cm` /
`TempOrga@123` (rôle `super_admin`, mot de passe à changer après la première
connexion — **ne jamais supprimer ce compte**).

**Démo locale (`demo_seed.sql`)** : 3 organisateurs (`orga1@ivote.cm`,
`orga2@ivote.cm`, `orga3@ivote.cm` / `TempOrga@123`), campagnes, candidats,
votes et retraits. Autoportant + idempotent (ré-applicable sans doublons).
**À ne pas appliquer en production.**

---

## 🚀 Comment exécuter

### 1. Base de données
```bash
sudo systemctl start mariadb
# 1re fois (ou après une réinstallation de MariaDB) :
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS ivote CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ivote_app'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
CREATE USER 'ivote_app'@'127.0.0.1' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
GRANT ALL PRIVILEGES ON ivote.* TO 'ivote_app'@'localhost';
GRANT ALL PRIVILEGES ON ivote.* TO 'ivote_app'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
mysql -h 127.0.0.1 -u ivote_app -p ivote < backend/schema.sql
# (optionnel, local uniquement) données de démonstration :
mysql -h 127.0.0.1 -u ivote_app -p ivote < backend/demo_seed.sql
```

### 2. Backend (API)
```bash
cp backend/.env.example backend/.env   # puis renseigner les valeurs
cd backend
php -S 0.0.0.0:8080 router.php
```
Vérif : `curl http://127.0.0.1:8080/api/campaigns`

### 3. Web (Flutter)
```bash
cd mobile
flutter build web
cd build/web
python3 -m http.server 3000 --bind 0.0.0.0
```
Ouvrir **http://localhost:3000**.

### 4. Mobile (APK)
```bash
cd mobile
flutter build apk --dart-define=API_BASE_URL=http://<IP_MACHINE>:8080
# APK : build/app/outputs/flutter-apk/app-release.apk
```

---

## 🌐 Déploiement VPS (nginx + `php -S`)

Le déploiement est réalisé par **l'administrateur du VPS** avec sudo ; le
backend est lancé en boucle locale derrière un reverse proxy nginx. Toute la
procédure est documentée dans
[`backend/deploy/DEPLOYMENT.md`](backend/deploy/DEPLOYMENT.md).

```bash
# 1) Installation initiale (une commande, en root)
git clone https://github.com/backslash057/IVote.git ivote-setup
cd ivote-setup/backend/deploy && sudo ./setup.sh
#    → utilisateur système 'ivote', code dans /opt/ivote/app, .env généré,
#      port libre choisi, base initialisée (auto si root MySQL), service
#      systemd 'ivote' + nginx pour ivote.arited.org installés.

# 2) HTTPS (recommandé)
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d ivote.arited.org

# 3) Mises à jour
cd /opt/ivote/app/backend/deploy && sudo ./deploy.sh   # git pull + migrations + restart
```

Architecture (le backend écoute en boucle locale, jamais exposé) :

```
Clients → https://ivote.arited.org → nginx
            ├─ /public/  /uploads/*  → fichiers statiques
            └─ /         → proxy → php -S 127.0.0.1:41824 (service ivote)
```

Points d'attention :

1. `/opt/ivote/app/backend/.env` (chmod 600) renseigné avec `DB_USER`/`DB_PASS`
   si la base n'a pas été initialisée automatiquement par `setup.sh`.
2. Le routeur (`router.php`) est la **seule** cible proxifiée ; `config.php`,
   `controllers/`, `routes/`, `.sql` répondent 404 depuis l'extérieur.
3. HTTPS : `sudo certbot --nginx -d vosdomaines.tld` (activation automatique du
   `secure` des cookies via `X-Forwarded-Proto`).
4. `IVOTE_ALLOWED_ORIGINS` à renseigner si le front Flutter web est servi depuis
   un autre domaine.
5. Uploads : `client_max_body_size 8m` (nginx) et `upload_max_filesize` ≥ 6M
   (php.ini) — au-delà, les images sont rejetées.

---

## 🌐 URLs publiques des campagnes

- `/vote/{campaign_code}` : page publique de campagne et de vote.
- `/campaigns/{campaign_code}` : tableau de bord de l'organisateur.

Le code est un identifiant stable de 7 consonnes majuscules, distinct de
`campaign_id`. Les API et relations internes continuent d'utiliser
`campaign_id`.

## 🔌 API (`/api/*`)

```
GET  /api/status                        → santé de l'API
GET  /api/campaigns                     → liste publique
GET  /api/campaigns/{id}                → détail public
POST /api/campaigns/{id}/votes          → voter (public)
POST /api/auth/login | /api/auth/signup → token JWT + user
GET  /api/auth/me                       → utilisateur courant
GET  /api/my-campaigns                  → campagnes de l'organisateur
POST /api/campaigns                     → création (auth)
POST /api/campaigns/{id}/status         → publish / close
POST /api/campaigns/{id}/relaunch       → relancer
POST /api/campaigns/{id}/candidates     → ajouter un candidat
POST /api/campaigns/{id}/payouts        → demande de retrait
GET  /api/campaigns/{id}/dashboard      → stats admin (revenus réels)
```

**Remises** appliquées au vote (identiques web/mobile) :
| Votes | Remise |
|---|---|
| ≥ 10 | 10 % |
| ≥ 20 | 15 % |
| ≥ 50 | 20 % |

---

## 📋 Itérations du projet

- ✅ Iteration 1 : Authentification, gestion des campagnes (CRUD) et Dashboard Organisateur
- ✅ Iteration 2 : Système de vote (simulation) et Dashboard Administrateur
- ✅ Iteration 4 : Application Flutter (web + mobile) connectée à l'API
- ⏳ Iteration 3 : Intégration du paiement et des retraits via Angaraa-Pay

---

## 🏷️ Crédits
Groupe 5 : Ntatchinda & Ngningtedem <br/>
Propulsé par **ARITeD**.