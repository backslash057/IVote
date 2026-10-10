#!/usr/bin/env bash
# =============================================================================
#  IVote — mise à jour de l'application (après déploiement initial)
#
#  Opérations :
#     1. git fetch + reset --hard sur la branche distante
#        (préserve les fichiers ignorés : .env et uploads — pas de `git clean`) ;
#     2. application des migrations SQL manquantes (journal schema_migrations) ;
#     3. redémarrage du service systemd 'ivote' ;
#     4. healthcheck /api/status.
#
#  À lancer en root (recommandé) ou avec sudo :
#      sudo ./deploy.sh
#
#  Options :
#      ./deploy.sh --no-migrate   # sans appliquer les migrations
#      ./deploy.sh --no-restart   # sans redémarrer le service
#
#  Variables : IVOTE_APP_DIR, IVOTE_BRANCH (défaut : main)
# =============================================================================
set -euo pipefail

IVOTE_BRANCH="${IVOTE_BRANCH:-main}"
if [ -n "${IVOTE_APP_DIR:-}" ]; then
    APP_DIR="$IVOTE_APP_DIR"
elif [ -d "/opt/ivote/app/.git" ]; then
    APP_DIR="/opt/ivote/app"
elif [ -d "$HOME/ivote-app/.git" ]; then
    APP_DIR="$HOME/ivote-app"
else
    echo "[IVote] Dépôt introuvable. Définissez IVOTE_APP_DIR." >&2
    exit 1
fi

BACKEND_DIR="$APP_DIR/backend"
ENV_FILE="$BACKEND_DIR/.env"
MIGRATIONS_DIR="$BACKEND_DIR/migrations"

DO_MIGRATE=1
DO_RESTART=1
for arg in "$@"; do
    case "$arg" in
        --no-migrate) DO_MIGRATE=0 ;;
        --no-restart) DO_RESTART=0 ;;
        *) echo "[IVote] Option inconnue : $arg" >&2; exit 1 ;;
    esac
done

SUDO=()
[ "$(id -u)" -eq 0 ] || SUDO=(sudo)

info() { printf '\033[1;32m[IVote]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[IVote]\033[0m %s\n' "$*" >&2; }

# -----------------------------------------------------------------------------
# 1. Code : fetch + reset sur la branche distante
# -----------------------------------------------------------------------------
info "Mise à jour du code ($IVOTE_BRANCH)…"
git -C "$APP_DIR" fetch --quiet origin "$IVOTE_BRANCH"
git -C "$APP_DIR" checkout --quiet -B "$IVOTE_BRANCH" "origin/$IVOTE_BRANCH"
git -C "$APP_DIR" reset --hard --quiet "origin/$IVOTE_BRANCH"
info "Code à jour : $(git -C "$APP_DIR" rev-parse --short HEAD)"

# -----------------------------------------------------------------------------
# 2. Configuration (.env)
# -----------------------------------------------------------------------------
[ -f "$ENV_FILE" ] || { echo "[IVote] .env introuvable dans $BACKEND_DIR." >&2; exit 1; }
# shellcheck disable=SC1090
set -a; . "$ENV_FILE"; set +a

# -----------------------------------------------------------------------------
# 3. Migrations SQL (idempotentes, journalisées)
# -----------------------------------------------------------------------------
MYSQL_BIN="$(command -v mysql || command -v mariadb || true)"
if [ "$DO_MIGRATE" -eq 1 ] && [ -n "$MYSQL_BIN" ] && [ -n "${DB_USER:-}" ] && [ -n "${DB_PASS:-}" ]; then
    if MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USER" -e 'SELECT 1' >/dev/null 2>&1; then
        MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USER" "${DB_NAME:-ivote}" \
            -e "CREATE TABLE IF NOT EXISTS schema_migrations (filename VARCHAR(255) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);"
        if [ -d "$MIGRATIONS_DIR" ]; then
            for file in "$MIGRATIONS_DIR"/*.sql; do
                [ -e "$file" ] || continue
                name="$(basename "$file")"
                already="$(MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USER" "${DB_NAME:-ivote}" -N -e "SELECT 1 FROM schema_migrations WHERE filename='$name' LIMIT 1;" 2>/dev/null || true)"
                if [ "$already" = "1" ]; then
                    continue
                fi
                info "Application de la migration : $name"
                MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USER" "${DB_NAME:-ivote}" < "$file"
                MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USER" "${DB_NAME:-ivote}" -e "INSERT INTO schema_migrations (filename) VALUES ('$name');"
            done
        fi
        info "Migrations à jour."
    else
        warn "Base de données injoignable — migrations ignorées."
    fi
elif [ "$DO_MIGRATE" -eq 1 ]; then
    warn "Client MySQL ou identifiants absents — migrations ignorées."
fi

# -----------------------------------------------------------------------------
# 4. Redémarrage du service systemd
# -----------------------------------------------------------------------------
PORT="${IVOTE_PORT:-41824}"
if [ "$DO_RESTART" -eq 1 ]; then
    if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files 2>/dev/null | grep -q '^ivote\.service'; then
        "${SUDO[@]}" systemctl restart ivote
        info "Service systemd 'ivote' redémarré."
    elif [ -x "$APP_DIR/run.sh" ]; then
        warn "Service systemd 'ivote' absent — relancez l'app manuellement : $APP_DIR/run.sh"
    else
        warn "Ni service systemd ni run.sh trouvés : redémarrage manuel requis."
    fi
fi

# -----------------------------------------------------------------------------
# 5. Vérification
# -----------------------------------------------------------------------------
sleep 1
if command -v curl >/dev/null 2>&1; then
    if curl -fsS "http://127.0.0.1:$PORT/api/status" >/dev/null 2>&1; then
        info "Healthcheck OK : /api/status répond sur le port $PORT."
    else
        warn "Healthcheck en échec — consultez 'systemctl status ivote' ou 'journalctl -u ivote -n 50'."
        exit 2
    fi
fi

info "Déploiement terminé."