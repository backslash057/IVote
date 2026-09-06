# IVote

IVote est une application de vote monétisée disponible sur web et mobile.

---

## 📌 Description du Projet

**IVote** est une plateforme de gestion de campagnes et concours de vote payants. Elle permet de créer des campagnes, présenter des candidats, collecter des votes payants de manière sécurisée et gérer les demandes de retrait de revenus.

---

## 🏗️ Architecture du projet
```
IVote/
├── backend/
│   ├── config.php
│   ├── controllers/
│   ├── public/
│   ├── router.php
│   ├── routes/
│   ├── schema.sql
│   ├── utils/
│   └── views/
├── frontend/
│   ├── public/
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   └── utils/
│   ├── package.json
│   └── vite.config.ts
└── mobile/
    ├── lib/
    └── pubspec.yaml
```
- **Backend (`/backend`)** : API REST développée en PHP natif avec MySQL (`schema.sql`, `config.php`, `router.php`, routes et contrôleurs).
- **Frontend (`/frontend`)** : Application Web SPA construite avec React, TypeScript et Vite pour les votants, organisateurs et administrateurs.
- **Mobile (`/mobile`)** : Application mobile développée avec Flutter (iOS / Android).

---

## 🚀 Comment Exécuter

### 1. Backend (PHP / MySQL)
1. Démarrez MySQL et importez `schema.sql` :
   ```bash
   mysql -u root -p nom_de_bdd < backend/schema.sql
   ```
2. Mettez à jour la configuration dans `backend/config.php`.
3. Démarrez le serveur :
   ```bash
   cd backend
   php -S router.php
   ```

### 2. Frontend (React / Vite)
```bash
cd frontend
npm install
npm run dev
```

### 3. Mobile (Flutter)
```bash
cd mobile
flutter pub get
flutter run
```

---

## 🔄 Itérations du Projet
- ✅ Iteration 1 : Authentification, gestion des campagnes (CRUD) et Dashboard Organisateur

- ✅ Iteration 2 : Système de vote (simulation) et Dashboard Administrateur

- ⏳ Iteration 3 : Intégration du paiement et des retraits de fonds via Angaraa-Pay

- ⏳ Iteration 4 : Développement et intégration de l'application mobile avec Flutter

## 🏷️ Crédits
Groupe 5: Ntatchinda & Ngningtedem <br/>
Propulsé par **ARITeD**.