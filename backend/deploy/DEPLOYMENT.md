# Feuille de route de déploiement — IVote

> Cible : **VPS Ubuntu** — domaine **ivote.arited.org** (déjà configuré),
> nginx en reverse proxy, backend PHP `php -S` sur la boucle locale,
> MariaDB/MySQL.
>
> Contraintes : le téléversement du code sur GitHub est fait par l'équipe,
> le **déploiement sur le VPS par l'administrateur** (`sudo ./deploy/setup.sh`) ;
> **aucune donnée fictive en base** ; le compte **super admin** est conservé et
> jamais supprimé.

```
Clients → https://ivote.arited.org → nginx (admin)
                      ├─ /public/*  /uploads/*   → fichiers statiques (nginx)
                      └─ /          → proxy → php -S 127.0.0.1:41824 (ivote)
```

---

## Phase 0 — Préparation du dépôt (sur la machine de dev)

1. Commit + push des correctifs de sécurité et des fichiers de déploiement.
2. Vérifier que `.env` n'est pas suivi par git (`.gitignore`).
3. `backend/schema.sql` = **production** : structure des tables + super admin
   `admin@ivote.cm` (mot de passe temporaire `TempOrga@123` — à changer après
   la première connexion). Idempotent, relançable.
4. `backend/demo_seed.sql` = démonstration locale uniquement, autoportant,
   idempotent (**ne pas l'appliquer en production**).

## Phase 1 — Reconnaissance serveur

| À vérifier | Attendue |
|---|---|
| OS | Ubuntu 22.04/24.04 |
| `php -v` | ≥ 8.0 (8.4 recommandé), extension `pdo_mysql` |
| `git` | présent |
| `mysql`/`mariadb` | présent + identifiants exploitables |
| Port | retenu automatiquement par setup.sh (défaut `41824`, plage haute) |
| nginx | géré par l'admin (dossier `/etc/nginx/…`) |
| Domaine | `ivote.arited.org` pointe vers le serveur (DNS ok) |

## Phase 2 — Déploiement initial (exécuté par l'admin, avec sudo)

```bash
# 1) Récupérer les scripts (repo public GitHub)
git clone https://github.com/backslash057/IVote.git ivote-setup

# 2) Lancer l'installation (une seule commande)
cd ivote-setup/backend/deploy
sudo ./setup.sh
```

`setup.sh` (en root) :
- vérifie les pré-requis (PHP ≥ 8.0, git, pdo_mysql) ;
- choisit un port libre en boucle locale (défaut `41824`, jamais exposé) ;
- crée l'utilisateur système dédié `ivote` et clone le code dans
  `/opt/ivote/app` (propriété `ivote`) ;
- génère `backend/.env` (secret JWT aléatoire, `IVOTE_TRUST_PROXY=1`) ;
- initialise la base : **automatiquement** si un accès `mysql` root par socket
  est disponible (création de la base `ivote`, de l'utilisateur `ivote_app`,
  import de `schema.sql`), sinon affiche le SQL à exécuter manuellement ;
- installe le service `ivote.service` et active nginx pour `ivote.arited.org` ;
- effectue un healthcheck `/api/status`.

Vérification : `curl http://127.0.0.1:PORT/api/status` → `success:true`
(port indiqué en fin de script) et `systemctl status ivote`.

> Sans sudo, le script préfixe lui-même les commandes privilégiées par `sudo`.

## Phase 3 — Après le déploiement

**3.1 Base non initialisée par le script ?** (pas d'accès root MySQL par socket)

```bash
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS ivote CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ivote_app'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
CREATE USER IF NOT EXISTS 'ivote_app'@'127.0.0.1' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
GRANT ALL PRIVILEGES ON ivote.* TO 'ivote_app'@'localhost';
GRANT ALL PRIVILEGES ON ivote.* TO 'ivote_app'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
sudo -u ivote mysql -h 127.0.0.1 -u ivote_app -p ivote < /opt/ivote/app/backend/schema.sql
# puis renseigner DB_USER/DB_PASS dans /opt/ivote/app/backend/.env et :
cd /opt/ivote/app/backend/deploy && sudo ./deploy.sh --no-migrate
```

**3.2 HTTPS** (recommandé)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d ivote.arited.org
```

Une fois HTTPS actif, les cookies sont marqués `secure` automatiquement
(le backend lit `X-Forwarded-Proto` posé par nginx).

## Phase 4 — Tests d'acceptance

| # | Test | Attendu |
|---|---|---|
| 1 | `curl https://ivote.arited.org/api/status` | `success:true` |
| 2 | Ouvrir `https://ivote.arited.org/` | page d'accueil web |
| 3 | `/login` avec `admin@ivote.cm` | accès admin (super admin conservé) |
| 4 | Créer un organisateur via `/signup` | compte créé |
| 5 | Créer une campagne, ajouter un candidat | ok |
| 6 | Voter sur une campagne publiée | `votes_added` + `amount_fcfa` |
| 7 | Upload d'image (jpg/png/webp) | classe le fichier, 404 sur les autres |
| 8 | `GET /api/campaigns` | liste publique |
| 9 | Requête sans token sur route privée | `401` |

## Phase 5 — Mises à jour & maintenance

**5.1 Mise à jour du code** (par l'admin ou l'utilisateur `ivote`)

```bash
cd /opt/ivote/app/backend/deploy && sudo ./deploy.sh
```

`deploy.sh` : `git fetch` + `reset --hard origin/main` (préserve `.env` et les
uploads : **pas** de `git clean`) → migrations SQL nouvelles (journalisées dans
`schema_migrations`) → redémarrage de `ivote.service` → healthcheck.

**5.2 Rollback** (si une version pose problème)

```bash
cd /opt/ivote/app && git log --oneline -5
git checkout <commit-précédent> && sudo ./backend/deploy/deploy.sh --no-migrate
```

**5.3 Sauvegarde de la base** (à planifier, idéalement par l'admin)

```bash
mysqldump -h 127.0.0.1 -u ivote_app -p ivote > ivote-$(date +%F).sql
```

**5.4 Supervision**

```bash
systemctl status ivote            # état du service
journalctl -u ivote -n 200        # journaux applicatifs
tail -f /var/log/nginx/access.log # accès web
```

---

## Dépannage rapide

| Symptôme | Cause probable → remède |
|---|---|
| `/api/status` → 500 « Configuration serveur incomplète » | `DB_USER`, `DB_PASS` ou `IVOTE_JWT_SECRET` absents dans `/opt/ivote/app/backend/.env` |
| API répond mais pages web en erreur | nginx mal pointé → refaire 3.3 avec le bon `__PORT__` |
| Login ne marche plus | l'app tournait sur une session/secret différent → relancer `deploy.sh` |
| Images 404 | fichiers absents de `backend/uploads` (toujours hors git) |
| `Address already in use` en lançant `run.sh` | port occupé → changer `IVOTE_PORT` et re-générer nginx |

## Sécurité — rappels

- 📁 `.env` : jamais commité, `chmod 600`.
- 🔒 Secret JWT : généré aléatoirement par setup.sh ; ne jamais réutiliser un
  secret d'un ancien dépôt.
- 🌐 Boucle locale uniquement : le backend n'est pas visible de l'internet,
  seul nginx l'est.
- 👤 Super admin : **conservé dans la base**, mot de passe à changer après la
  première connexion.