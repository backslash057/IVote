#!/usr/bin/env bash
# =============================================================================
#  IVote — installation / configuration du backend (VPS)
#
#  À exécuter par l'administrateur du VPS :
#      sudo ./setup.sh
#  (sans sudo, le script préfixe lui-même les opérations système par sudo).
#
#  Flux :
#    - pré-requis (PHP >= 8.0, git, extension pdo_mysql) ;
#    - choix d'un port libre en boucle locale (défaut 41824) ;
#    - clone / mise à jour du code dans /opt/ivote/app (root) ou ~/ivote-app ;
#    - génération de backend/.env (secret JWT aléatoire, IVOTE_TRUST_PROXY=1) ;
#    - initialisation de la base (auto si accès root MySQL, sinon SQL fourni) ;
#    - installation du service ivote.service + reverse proxy nginx ;
#    - healthcheck /api/status ;
#    - conseils HTTPS (certbot).
#
#  Usage :
#    ./setup.sh                          # valeurs par défaut
#    ./setup.sh --no-system              # sans systemd ni nginx
#    IVOTE_PORT=41999 ./setup.sh
#    DB_USER=ivote_app DB_PASS=secret ./setup.sh   # identifiants fournis
#
#  Variables reconnues :
#    IVOTE_REPO, IVOTE_BRANCH, IVOTE_PORT, IVOTE_APP_DIR, IVOTE_SERVER_NAME,
#    IVOTE_APP_USER, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_APP_USER
# =============================================================================
set -euo pipefail

IVOTE_REPO="${IVOTE_REPO:-https://github.com/backslash057/IVote.git}"
IVOTE_BRANCH="${IVOTE_BRANCH:-main}"
IVOTE_PORT="${IVOTE_PORT:-41824}"
IVOTE_SERVER_NAME="${IVOTE_SERVER_NAME:-ivote.arited.org}"
IVOTE_APP_USER="${IVOTE_APP_USER:-ivote}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-ivote}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
DB_APP_USER="${DB_APP_USER:-ivote_app}"

DO_SYSTEM=1
for arg in "$@"; do
    case "$arg" in
        --no-system) DO_SYSTEM=0 ;;
        *) echo "[IVote] Option inconnue : $arg" >&2; exit 1 ;;
    esac
done

ROOT=0
SUDO=()
if [ "$(id -u)" -eq 0 ]; then
    ROOT=1
    APP_DIR="${IVOTE_APP_DIR:-/opt/ivote/app}"
else
    APP_DIR="${IVOTE_APP_DIR:-$HOME/ivote-app}"
    command -v sudo >/dev/null 2>&1 || { echo "[IVote] sudo requis (ou lancez le script en root)." >&2; exit 1; }
    SUDO=(sudo)
fi
RUN_USER="$(id -un)"

info()  { printf '\033[1;32m[IVote]\033[0m %s\n' "$*"; }
warn()  { printf '\033[1;33m[IVote]\033[0m %s\n' "$*" >&2; }
die()   { printf '\033[1;31m[IVote]\033[0m %s\n' "$*" >&2; exit 1; }

gen_secret() {
    if command -v openssl >/dev/null 2>&1; then openssl rand -hex "$1"; else php -r "echo bin2hex(random_bytes($1));"; fi
}

gen_db_password() {
    # Satisfy common MySQL validate_password policies: upper/lowercase,
    # digits, special character, and sufficient length.
    printf 'IvA9!%s%s' "$(gen_secret 16)" "$(gen_secret 16)"
}
MYSQL_BIN="$(command -v mysql || command -v mariadb || true)"

# -----------------------------------------------------------------------------
# 0. Pré-requis
# -----------------------------------------------------------------------------
command -v php >/dev/null 2>&1 || die "PHP CLI introuvable. Installez php-cli (8.0+)."
command -v git >/dev/null 2>&1 || die "git introuvable."

PHP_VERSION="$(php -r 'echo PHP_VERSION;')"
php -r 'exit(version_compare(PHP_VERSION, "8.0.0", ">=") ? 0 : 1);' \
    || die "PHP >= 8.0 requis (détecté : $PHP_VERSION)."
php -m | grep -qi '^pdo_mysql$' \
    || warn "L'extension PHP pdo_mysql semble absente : le backend ne pourra pas se connecter à MySQL."
[ -n "$MYSQL_BIN" ] || warn "Client MySQL/MariaDB introuvable : la base ne pourra pas être initialisée par le script."

# -----------------------------------------------------------------------------
# 1. Choix d'un port libre (boucle locale uniquement)
# -----------------------------------------------------------------------------
port_free() {
    local port="$1"
    if command -v ss >/dev/null 2>&1; then
        [ -z "$(ss -Hltn "sport = :$port" 2>/dev/null || true)" ]
    elif command -v python3 >/dev/null 2>&1; then
        python3 - "$port" <<'PY'
import socket, sys
s = socket.socket()
s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
try:
    s.bind(("127.0.0.1", int(sys.argv[1])))
except OSError:
    sys.exit(1)
finally:
    s.close()
PY
    else
        warn "Ni 'ss' ni 'python3' : impossible de vérifier le port $port."
        return 0
    fi
}

PORT="$IVOTE_PORT"
tries=0
while ! port_free "$PORT"; do
    tries=$((tries + 1))
    [ "$tries" -gt 50 ] && die "Aucun port libre trouvé à partir de $IVOTE_PORT."
    PORT=$((PORT + 1))
done
[ "$PORT" != "$IVOTE_PORT" ] && warn "Port $IVOTE_PORT occupé → port retenu : $PORT"

# -----------------------------------------------------------------------------
# 2. Utilisateur système dédié (en root) + récupération du code
# -----------------------------------------------------------------------------
if [ "$ROOT" -eq 1 ]; then
    RUN_USER="$IVOTE_APP_USER"
    if ! id "$RUN_USER" >/dev/null 2>&1; then
        "${SUDO[@]}" useradd --system --create-home --shell /usr/sbin/nologin "$RUN_USER"
        info "Utilisateur système '$RUN_USER' créé."
    fi
fi

if [ -d "$APP_DIR/.git" ]; then
    info "Mise à jour du dépôt existant…"
    git -C "$APP_DIR" fetch --quiet origin "$IVOTE_BRANCH"
    git -C "$APP_DIR" checkout --quiet -B "$IVOTE_BRANCH" "origin/$IVOTE_BRANCH"
else
    mkdir -p "$(dirname "$APP_DIR")"
    info "Clonage de $IVOTE_REPO ($IVOTE_BRANCH) dans $APP_DIR…"
    git clone --quiet --branch "$IVOTE_BRANCH" "$IVOTE_REPO" "$APP_DIR"
fi

BACKEND_DIR="$APP_DIR/backend"
[ -f "$BACKEND_DIR/router.php" ] || die "Backend introuvable dans $BACKEND_DIR."

# -----------------------------------------------------------------------------
# 3. Base de données : mode d'accès retenu
#    a) identifiants fournis (DB_USER/DB_PASS) ;
#    b) exécution en root avec accès `mysql` par socket → provisioning auto ;
#    c) sinon → SQL à exécuter manuellement (affiché en fin de script).
# -----------------------------------------------------------------------------
mysql_socket_ok() {
    [ -n "$MYSQL_BIN" ] && "$MYSQL_BIN" -N -e 'SELECT 1' >/dev/null 2>&1
}

DB_BOOTSTRAPPED=0
if [ -n "$DB_USER" ] && [ -n "$DB_PASS" ]; then
    info "Identifiants DB fournis (utilisateur $DB_USER) — on les utilise tels quels."
elif [ "$ROOT" -eq 1 ] && mysql_socket_ok; then
    DB_USER="$DB_APP_USER"
    DB_PASS="$(gen_db_password)"
    info "Accès root MySQL par socket — provisioning automatique de la base '$DB_NAME'…"
    "$MYSQL_BIN" <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
    DB_BOOTSTRAPPED=1
else
    warn "Aucun identifiant MySQL : la base devra être préparée manuellement (SQL en fin de script)."
fi

# -----------------------------------------------------------------------------
# 4. Fichier de configuration .env
# -----------------------------------------------------------------------------
ENV_FILE="$BACKEND_DIR/.env"
if [ -f "$ENV_FILE" ]; then
    info ".env déjà présent — conservé tel quel (secrets inchangés)."
elif [ -z "$DB_USER" ]; then
    warn "DB_USER/DB_PASS non fournis : renseignez-les ensuite dans $ENV_FILE."
    cat > "$ENV_FILE" <<EOF
# Généré par deploy/setup.sh — NE PAS COMMITTER.
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_NAME=$DB_NAME
DB_USER=
DB_PASS=

IVOTE_JWT_SECRET=$(gen_secret 48)
IVOTE_PORT=$PORT
IVOTE_TRUST_PROXY=1
IVOTE_ALLOWED_ORIGINS=https://$IVOTE_SERVER_NAME
EOF
    chmod 600 "$ENV_FILE"
else
    cat > "$ENV_FILE" <<EOF
# Généré par deploy/setup.sh — NE PAS COMMITTER.
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS

IVOTE_JWT_SECRET=$(gen_secret 48)
IVOTE_PORT=$PORT
IVOTE_TRUST_PROXY=1
IVOTE_ALLOWED_ORIGINS=https://$IVOTE_SERVER_NAME
EOF
    chmod 600 "$ENV_FILE"
fi
info "Configuration écrite : $ENV_FILE (chmod 600)."

# -----------------------------------------------------------------------------
# 5. Initialisation effective de la base (tables + super admin)
# -----------------------------------------------------------------------------
if [ -n "$MYSQL_BIN" ] && [ -n "$DB_USER" ]; then
    if MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -e 'SELECT 1' >/dev/null 2>&1; then
        info "Connexion MySQL OK — import de schema.sql (structure + super admin)…"
        MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" < "$BACKEND_DIR/schema.sql"
        info "Base '$DB_NAME' prête."
        DB_BOOTSTRAPPED=1
    else
        warn "Connexion MySQL impossible avec les identifiants retenus — base non initialisée."
    fi
fi

# -----------------------------------------------------------------------------
# 6. Script de lancement manuel (utile hors systemd / conteneur)
# -----------------------------------------------------------------------------
WORKERS=""
if php -m | grep -qi '^pcntl$'; then
    WORKERS="export PHP_CLI_SERVER_WORKERS=4"
fi

cat > "$APP_DIR/run.sh" <<EOF
#!/usr/bin/env bash
# Lancement manuel du backend IVote (php -S sur la boucle locale).
set -euo pipefail
$WORKERS
cd "$BACKEND_DIR"
exec php -S 127.0.0.1:$PORT router.php
EOF
chmod +x "$APP_DIR/run.sh"

# --- Propriété des fichiers (root) : l'app doit pouvoir écrire dans uploads/ ---
if [ "$ROOT" -eq 1 ]; then
    chown -R "$RUN_USER":"$RUN_USER" "$APP_DIR"
    info "Propriété de $APP_DIR attribuée à '$RUN_USER'."
fi

# -----------------------------------------------------------------------------
# 7. Artefacts nginx + systemd, puis installation (si --no-system non passé)
# -----------------------------------------------------------------------------
OUT_DIR="$APP_DIR/deploy/generated"
mkdir -p "$OUT_DIR"
if [ -f "$APP_DIR/backend/deploy/nginx.conf" ]; then
    sed -e "s|__PORT__|$PORT|g" \
        -e "s|__APP_ROOT__|$BACKEND_DIR|g" \
        -e "s|__SERVER_NAME__|$IVOTE_SERVER_NAME|g" \
        "$APP_DIR/backend/deploy/nginx.conf" > "$OUT_DIR/nginx.conf"
fi
if [ -f "$APP_DIR/backend/deploy/ivote.service" ]; then
    sed -e "s|__PORT__|$PORT|g" \
        -e "s|__APP_ROOT__|$BACKEND_DIR|g" \
        -e "s|__RUN_USER__|$RUN_USER|g" \
        "$APP_DIR/backend/deploy/ivote.service" > "$OUT_DIR/ivote.service"
fi
info "Artefacts générés : $OUT_DIR/{nginx.conf, ivote.service}"

if [ "$DO_SYSTEM" -eq 1 ]; then
    if command -v systemctl >/dev/null 2>&1 && [ -d /etc/systemd/system ]; then
        "${SUDO[@]}" install -m 644 "$OUT_DIR/ivote.service" /etc/systemd/system/ivote.service
        "${SUDO[@]}" systemctl daemon-reload
        "${SUDO[@]}" systemctl enable --now ivote.service
        info "Service systemd 'ivote' installé et démarré (port $PORT)."
    else
        warn "systemd absent : service non installé. Lancement manuel via $APP_DIR/run.sh."
    fi

    if command -v nginx >/dev/null 2>&1 && [ -f "$OUT_DIR/nginx.conf" ]; then
        "${SUDO[@]}" install -m 644 "$OUT_DIR/nginx.conf" "/etc/nginx/sites-available/$IVOTE_SERVER_NAME"
        "${SUDO[@]}" ln -sf "/etc/nginx/sites-available/$IVOTE_SERVER_NAME" "/etc/nginx/sites-enabled/$IVOTE_SERVER_NAME"
        if "${SUDO[@]}" nginx -t >/dev/null 2>&1; then
            "${SUDO[@]}" systemctl reload nginx 2>/dev/null || true
            info "nginx rechargé : http://$IVOTE_SERVER_NAME → 127.0.0.1:$PORT"
        else
            warn "nginx -t a échoué — activation annulée. Vérifiez $OUT_DIR/nginx.conf."
        fi
    else
        warn "nginx absent : configuration non installée."
    fi
else
    warn "--no-system : service systemd et nginx NON installés."
fi

# -----------------------------------------------------------------------------
# 8. Healthcheck
# -----------------------------------------------------------------------------
HEALTH=0
if [ "$ROOT" -eq 1 ] && command -v systemctl >/dev/null 2>&1 && systemctl is-active --quiet ivote 2>/dev/null; then
    sleep 1
    if curl -fsS "http://127.0.0.1:$PORT/api/status" >/dev/null 2>&1; then
        HEALTH=1
        info "Healthcheck OK : /api/status répond sur $PORT."
    else
        warn "Service actif mais /api/status ne répond pas — voir 'journalctl -u ivote -n 50'."
    fi
else
    info "Lancement temporaire pour vérification…"
    ( cd "$BACKEND_DIR" && exec php -S 127.0.0.1:"$PORT" router.php ) >/tmp/ivote-setup.log 2>&1 &
    _pid=$!
    sleep 1
    if curl -fsS "http://127.0.0.1:$PORT/api/status" >/dev/null 2>&1; then
        HEALTH=1
        info "Healthcheck OK : http://127.0.0.1:$PORT/api/status répond."
    else
        warn "Healthcheck en échec — voir /tmp/ivote-setup.log."
    fi
    kill "$_pid" 2>/dev/null || true
    pkill -P "$_pid" 2>/dev/null || true
    wait "$_pid" 2>/dev/null || true
fi

# -----------------------------------------------------------------------------
# 9. Récapitulatif
# -----------------------------------------------------------------------------
cat <<EOF

============================================================
 IVote — installation terminée (dossier : $APP_DIR)
============================================================
 Backend         : $BACKEND_DIR
 Utilisateur     : $RUN_USER
 Port applicatif : $PORT  (127.0.0.1 uniquement)
 Domaine         : https://$IVOTE_SERVER_NAME
 Healthcheck     : $([ "$HEALTH" -eq 1 ] && echo "OK" || echo "À VÉRIFIER")
 Service systemd : $([ "$DO_SYSTEM" -eq 1 ] && [ -d /etc/systemd/system ] && echo "ivote (actif)" || echo "non installé")

 Vérifications :
   curl http://127.0.0.1:$PORT/api/status
   systemctl status ivote
EOF

if [ "$DB_BOOTSTRAPPED" -eq 1 ]; then
    echo " Base de données  : $DB_NAME (utilisateur $DB_USER) — initialisée."
else
    cat <<EOF

--- Base de données à préparer manuellement (sudo) ----------------------
 1) Connexion root MySQL (socket) :
      sudo mysql

 2) Création de la base + de l'utilisateur applicatif :
      CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
      CREATE USER IF NOT EXISTS '$DB_APP_USER'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
      CREATE USER IF NOT EXISTS '$DB_APP_USER'@'127.0.0.1' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
      GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_APP_USER'@'localhost';
      GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_APP_USER'@'127.0.0.1';
      FLUSH PRIVILEGES;

 3) Import du schéma + renseigner les identifiants :
      mysql -h 127.0.0.1 -u $DB_APP_USER -p $DB_NAME < $BACKEND_DIR/schema.sql
      # puis éditer $ENV_FILE avec DB_USER / DB_PASS
------------------------------------------------------------------------
EOF
fi

cat <<EOF

--- HTTPS (recommandé) --------------------------------------------------
   sudo apt install certbot python3-certbot-nginx
   sudo certbot --nginx -d $IVOTE_SERVER_NAME
------------------------------------------------------------------------
EOF

info "Terminé."