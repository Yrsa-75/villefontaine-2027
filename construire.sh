#!/usr/bin/env bash
# Construit dist/ : un .zip installable par élément + versions.json (lu par le plugin Déploiement).
set -euo pipefail
cd "$(dirname "$0")"
# Tout est construit dans un dossier provisoire, échangé avec dist/ à la fin seulement :
# une construction interrompue ne laisse jamais un dist/ incomplet (le plugin Déploiement refuserait alors d'installer).
rm -rf dist.tmp && mkdir dist.tmp
trap 'rm -rf dist.tmp' EXIT
echo "{" > dist.tmp/versions.json
premier=1
for c in socle-villefontaine villefontaine actualites-villefontaine agenda-villefontaine annuaire-villefontaine kiosque-villefontaine securite-villefontaine deploiement-villefontaine; do
  if [ "$c" = "villefontaine" ]; then f="$c/style.css"; else f="$c/$c.php"; fi
  v=$(grep -m1 -E '^\s*\*?\s*Version:' "$f" | sed -E 's/.*Version:\s*//' | tr -d '\r ')
  (zip -qrX "dist.tmp/$c.zip" "$c" -x '*.DS_Store')
  [ $premier = 1 ] || echo "," >> dist.tmp/versions.json
  printf '  "%s": "%s"' "$c" "$v" >> dist.tmp/versions.json
  premier=0
  echo "$c $v"
done
printf '\n}\n' >> dist.tmp/versions.json
mkdir -p dist.tmp/migration && cp outils/migration/pages.json outils/migration/contenus.json dist.tmp/migration/
rm -rf dist && mv dist.tmp dist
