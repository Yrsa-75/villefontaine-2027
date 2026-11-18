#!/usr/bin/env bash
# Construit dist/ : un .zip installable par élément + versions.json (lu par le plugin Déploiement).
set -euo pipefail
cd "$(dirname "$0")"
rm -rf dist && mkdir dist
echo "{" > dist/versions.json
premier=1
for c in socle-villefontaine villefontaine actualites-villefontaine agenda-villefontaine annuaire-villefontaine kiosque-villefontaine deploiement-villefontaine; do
  if [ "$c" = "villefontaine" ]; then f="$c/style.css"; else f="$c/$c.php"; fi
  v=$(grep -m1 -E '^\s*\*?\s*Version:' "$f" | sed -E 's/.*Version:\s*//' | tr -d '\r ')
  (zip -qrX "dist/$c.zip" "$c" -x '*.DS_Store')
  [ $premier = 1 ] || echo "," >> dist/versions.json
  printf '  "%s": "%s"' "$c" "$v" >> dist/versions.json
  premier=0
  echo "$c $v"
done
printf '\n}\n' >> dist/versions.json
mkdir -p dist/migration && cp outils/migration/pages.json dist/migration/pages.json
