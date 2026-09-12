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
│   ├── router.php                   # Routeur (web + API /api/*, CORS)
│   ├── schema.sql                   # Schéma + seed organisateur
│   ├── controllers/
│   │   ├── apiController.php        # Couche REST JSON (web/mobile)
│   │   ├── authController.php       # login / signup / me (JWT)
│   │   └── campaignController.php   # campagnes, candidats, votes, retraits
│   ├── routes/                      # Pages web PHP (admin)
│   ├── public/                      # Assets statiques
│   ├── uploads/                     # Images candidates / campagnes
│   └── utils/tokenizer.php          # JWT HS256 (signature + expiration)
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

Le backend lit les secrets via variables d'environnement (fallbacks **dev** dans le code) :

| Variable | Valeur par défaut (dev) |
|---|---|
| `DB_HOST` | `127.0.0.1` |
| `DB_USER` | `backslash057` |
| `DB_PASS` | `root` |
| `DB_NAME` | `ivote` |
| `IVOTE_JWT_SECRET` | clé de dev |
| `API_BASE_URL` (Flutter) | `http://127.0.0.1:8080` |

**Utilisateur seed** : `orga@gmail.com` / `TempOrga@123` (inséré par `schema.sql`).

---

## 🚀 Comment exécuter

### 1. Base de données
```bash
sudo systemctl start mariadb
# 1re fois (ou après une réinstallation de MariaDB) :
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS ivote CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'backslash057'@'localhost' IDENTIFIED BY 'root';
CREATE USER 'backslash057'@'127.0.0.1' IDENTIFIED BY 'root';
GRANT ALL PRIVILEGES ON ivote.* TO 'backslash057'@'localhost';
GRANT ALL PRIVILEGES ON ivote.* TO 'backslash057'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
mysql -h 127.0.0.1 -u backslash057 -proot ivote < backend/schema.sql
```

### 2. Backend (API)
```bash
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