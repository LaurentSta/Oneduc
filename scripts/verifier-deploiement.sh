#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -ne 1 ] || [[ "$1" != https://* ]]; then
    echo "Usage : bash scripts/verifier-deploiement.sh https://votre-domaine" >&2
    exit 1
fi

adresse="${1%/}"
dossier=$(mktemp -d)
trap 'rm -rf "$dossier"' EXIT

# Pas de redirection silencieuse ni de désactivation de la vérification TLS.
statut=$(curl --fail --silent --show-error --connect-timeout 10 --max-time 30 \
    -H 'Accept: application/json' -D "$dossier/entetes" \
    "$adresse/up" -o "$dossier/sante.json" --write-out '%{http_code}')
if [ "$statut" != 200 ]; then
    echo "Le contrôle /up ne répond pas avec le statut 200." >&2
    exit 1
fi
php -r '
    $sante = json_decode(file_get_contents($argv[1]), true);
    if (($sante["status"] ?? null) !== "up") {
        fwrite(STDERR, "Le contrôle /up ne confirme pas la disponibilité.\n");
        exit(1);
    }
' "$dossier/sante.json"

if ! grep -Eiq '^X-Content-Type-Options: nosniff[[:space:]]*$' "$dossier/entetes"; then
    echo "En-tête nosniff absent du contrôle de santé." >&2
    exit 1
fi

statut=$(curl --silent --show-error --connect-timeout 10 --max-time 30 \
    --output /dev/null --write-out '%{http_code}' "$adresse/login")
if [ "$statut" != 200 ]; then
    echo "La page de connexion ne répond pas avec le statut 200." >&2
    exit 1
fi

echo "Contrôles HTTP réussis : /up, en-tête nosniff et /login."
