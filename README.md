# Villefontaine 2027

Code du nouveau site de la Ville de Villefontaine (WordPress, édition complète du site).

| Dossier | Rôle |
|---|---|
| `villefontaine/` | Thème (apparence uniquement) |
| `socle-villefontaine/` | Blocs de mise en page : en-tête, méga-menu, « Vos démarches », visuel d’accueil, sections… |
| `actualites-villefontaine/` | Actualités et mise « À la une » |
| `agenda-villefontaine/` | Agenda : événements ponctuels, sur plusieurs jours, récurrents |
| `annuaire-villefontaine/` | Annuaire des lieux, carte (Leaflet + Plan IGN), import Excel |
| `kiosque-villefontaine/` | Publications, lecteur Calaméo après consentement |
| `deploiement-villefontaine/` | Installation et mise à jour depuis ce dépôt |
| `dist/` | Fichiers `.zip` prêts à installer + `versions.json` |

## Publier une nouvelle version

1. Modifier le code, puis augmenter le numéro `Version:` de l’élément.
2. Lancer `./construire.sh` (régénère `dist/`).
3. Envoyer sur GitHub (`git push`).
4. Sur le site : **Outils > Déploiement Villefontaine**, bouton « Mettre à jour ».

Toujours tester sur la préproduction avant la production.
