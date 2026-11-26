# Villefontaine 2027 : déploiement et état du chantier

Mis à jour le 26/11/2026 (pages Budget, Grands rendez-vous, Jumelage et Services municipaux recomposées, Agenda 1.1.4 ; menu aligné sur le document « ARCHITECTURE SITE NEW » ; règle des images : WebP, 2 400 px de large au plus ; voile du grand visuel allégé ; plugin Sécurité 1.1.0. Socle 1.5.12, thème 1.0.16, Agenda 1.1.4, Sécurité 1.1.0, Déploiement 1.3.4).

Ce document existe en deux exemplaires identiques : dans le projet Claude (`claude/deploiement-et-etat-du-chantier.md`) et dans le dépôt (`docs/etat-du-chantier.md`).

## Accès à l'administration (depuis le 25/11)
- Adresse de connexion de la préprod : https://preprod.villefontaine.fr/backoffice
- `wp-login.php` et `/wp-admin/` répondent « page introuvable » aux visiteurs non connectés. Une fois connecté, le tableau de bord reste à l'adresse /wp-admin/ (fonctionnement normal de WordPress) ; si la session a expiré, repasser par /backoffice.
- Les réglages de sécurité sont dans le menu « Sécurité » de l'administration (administrateurs uniquement), six onglets : Vue d'ensemble, Connexion, Comptes, Protection du site, Page de connexion, Journal.
- En cas de blocage total (adresse oubliée, tout le monde bloqué, site en erreur après un changement de réglage) : ajouter `define( 'VF_SECURITE_PAUSE', true );` dans `wp-config.php` (gestionnaire de fichiers Hostinger). Toutes les protections se mettent en veille, la connexion classique par `wp-login.php` revient. Retirer la ligne ensuite.
- Si le site entier répond « erreur 500 » : ouvrir le fichier `.htaccess` à la racine du site (gestionnaire de fichiers Hostinger) et supprimer le bloc compris entre `# BEGIN Securite Villefontaine` et `# END Securite Villefontaine`.

## Où est le code
- Dépôt GitHub public : https://github.com/Yrsa-75/villefontaine-2027
- Un dossier par élément : `villefontaine` (thème), `socle-villefontaine`, `actualites-villefontaine`, `agenda-villefontaine`, `annuaire-villefontaine`, `kiosque-villefontaine`, `securite-villefontaine`, `deploiement-villefontaine`.
- `outils/migration/` : scripts de conversion de l'ancien site (plan.py = arborescence et menu, menu.py = construction du méga-menu depuis le plan, construire.py, convertir.py, surcharges.py = pages recomposées ou écrites à la main, recompositions.py = pages refaites d'après le document d'architecture, thematiques.py = classement des événements, urbanisme.py = page Démarches d'urbanisme et données du schéma, signalement.py = page Signalement, alts.py = textes alternatifs des images) et lots produits (pages.json, contenus.json).
- `dist/` : les .zip prêts à installer, `versions.json` et `dist/migration/` (régénérés par `./construire.sh`).
- `docs/etat-du-chantier.md` : ce document.

## Publier une mise à jour
1. Modifier le code et augmenter le numéro `Version:` de l'élément (et `"version"` dans les block.json des blocs dont le CSS ou le JS a changé : c'est ce numéro qui renouvelle le cache).
2. `./construire.sh`, puis `git push`. Le script construit dans un dossier provisoire (`dist.tmp`) et ne remplace `dist/` qu'une fois complet. Toujours le laisser aller au bout (ne jamais couper sa sortie avec `| head`) et vérifier que `dist/versions.json` liste bien les 8 éléments : un manifeste incomplet bloque toute installation (« pas encore publié dans le dépôt »).
3. Attendre environ 1 minute (cache de l'API GitHub), puis sur la préprod : Outils > Déploiement Villefontaine > « Mettre à jour ».
   Vérifier ensuite que la version installée correspond bien à la version disponible.
4. Claude installe lui-même via le navigateur Chrome de Ju (session WordPress ouverte dans Chrome, connexion par /backoffice) : l'ordinateur doit être allumé avec Chrome ouvert et l'extension Claude connectée. Le navigateur intégré de l'application Claude n'est pas connecté à l'admin : il sert seulement à contrôler le rendu public. Attention : la session de Ju se ferme désormais après 2 heures sans activité (réglable), il faut alors qu'il se reconnecte lui-même. Un appel qui dure plus de 45 secondes est coupé côté navigateur (le traitement continue sur le serveur) : traiter par petits lots et recontrôler ensuite.
5. Depuis une session Claude dans le cloud, le dépôt peut être rattaché en écriture (push direct sur main).
6. Après une mise à jour du plugin Sécurité : ouvrir une page de l'administration en tant qu'administrateur (c'est à ce moment que les règles du fichier .htaccess sont réécrites et vérifiées), puis contrôler l'onglet Sécurité > Vue d'ensemble.

## Plugin « Déploiement Villefontaine » (1.3.4)
- Installe ou met à jour uniquement les 8 éléments du projet, depuis le dépôt (adresse figée sur le dernier commit).
- Réservé aux administrateurs ; actions protégées par le jeton anti-falsification de WordPress.
- Importe des images et documents depuis villefontaine.fr (sans doublon), et les couvertures Calaméo.
- Couvertures Calaméo : l'image fournie par Calaméo est une vignette de partage 1200×628 (couverture sur fond gris foncé, logo Calaméo). Elle est recadrée sur la couverture seule à l'import. Route `/vf-deploiement/v1/couvertures` (POST) : recadre les couvertures déjà importées et refait leurs miniatures (fait le 24/11 : 6 couvertures, 454×570).
- Images existantes : route `/vf-deploiement/v1/images` (POST), voir la section « Images » ci-dessous.
- Migration (route REST /vf-deploiement/v1/migration) : étapes `pages`, `finaliser` (menu, redirections, panneau Vos démarches), `actualites`, `evenements`. Relançable sans créer de doublons (clé de migration). Les événements gardent leur adresse d'origine.
- Une seule page : `{etape: 'pages', cle: '<clé de la page>'}` crée ou met à jour cette page uniquement, sans toucher aux autres (à privilégier désormais). Les pages du lot marquées « brouillon » sont créées en brouillon.
- Attention : `finaliser` réécrit le méga-menu depuis le lot et la table des redirections ; il faut relancer `actualites` et `evenements` après. Toute retouche du menu faite à la main dans Apparence > Menus serait perdue : reporter d'abord la retouche dans `plan.py`.
- Attention : relancer `pages` sans clé remplace le contenu de toutes les pages migrées. Avant chaque relance, vérifier qu'aucune page n'a été modifiée à la main ; à ne plus faire une fois que les agents ont commencé à modifier les pages.

## Versions
- Installées sur la préprod (26/11) : Socle 1.5.12, thème 1.0.16, Actualités 1.0.5, Agenda 1.1.4, Annuaire 1.0.6, Kiosque 1.0.2, Sécurité 1.1.0, Déploiement 1.3.4.
- Migration relancée le 19/11 au soir (pages, finaliser, actualites, evenements) : 91 pages migrées, 167 événements sans doublon, 178 redirections.
- Nom du site passé à « Ville de Villefontaine » sur la préprod (19/11).

## Menu et plan du site (document « ARCHITECTURE SITE NEW » de Ju, 25/11)
Ce document fait foi pour le méga-menu. Le menu de la préprod suivait le plan d'octobre (mêmes 4 rubriques) mais s'en écartait sur trois points, corrigés le 25/11.
- Pages sorties du menu mais gardées en ligne (décision de Ju) : Instances citoyennes, Maison du pouvoir d'achat, Chantier d'insertion, Risques majeurs, Logement social, Énergie : infos et conseils, Nuisibles et espèces invasives. Elles restent listées dans la page de leur rubrique (« Dans cette rubrique ») et trouvées par la recherche.
- Les grands projets : plus de sous-menu ; la page affiche 3 cartes cliquables (Cœur de ville, La rénovation urbaine, Campus de la construction durable).
- Intitulés du menu repris du document (le titre des pages n'a pas changé) : Portage de repas à domicile, Zones d'activités économiques, Commerce de proximité, The Village outlet, Les grands rendez-vous sportifs et culturels.
- Lien ajouté : « Villefontaine recrute » sous Économie, emploi et insertion (la page reste rangée dans La mairie).
- Six pages du plan sans contenu sur le site actuel, créées en brouillon et hors menu (décision de Ju) : Joindre les élus (page n° 1840), Plan canicule (1841), Handicap (1842), Relais emploi du CCAS (1843), Infos Enedis et Semidao (1844), La collecte (1845). Quand la mairie fournit le texte : le saisir, publier la page, puis l'ajouter au menu dans Apparence > Menus.
- Où c'est écrit : options `hors_menu`, `menu_titre`, `liens_menu` et `statut: draft` + `attente` dans `outils/migration/plan.py` ; `python3 menu.py` régénère le menu et les brouillons dans `pages.json` sans avoir besoin de l'export de l'ancien site. Sur la préprod, le menu a été retouché élément par élément (68 éléments au lieu de 77), sans relancer `finaliser`.
- Le reste du document de Ju concerne le contenu des pages, pas le menu : voir la section suivante.

## Contenu des pages (passages en bleu du document d'architecture)
Vérification du 25/11 : 9 des 17 contenus en bleu étaient déjà conformes (8 raccourcis de l'accueil, Commerces, Tranquillité, Accessibilité, Grands projets, Vie culturelle, Sports et loisirs, Jardins, Patrimoine). Corrections validées par Ju, dans cet ordre, une page à la fois :
1. **Budget : fait le 25/11.** La page Budget de la commune affiche 6 rectangles (bloc « Pages de la rubrique », donc automatique : une page ajoutée sous Budget crée son rectangle) vers 6 sous-pages hors menu : Budget 2026, 2025, 2024, 2023, 2022 et Les chiffres clés, plus un court « Comprendre les documents budgétaires ». Chaque page annuelle liste les documents de l'année (rapport d'orientation budgétaire, budget primitif, compte de l'année précédente). Les documents 2022 étaient dans la médiathèque du site actuel sans être affichés : ils sont repris (rapport d'orientation 2022, budget primitif 2022, compte administratif 2021). Pour ajouter une année : créer une page « Budget 2027 » avec Budget de la commune comme page parente, ordre 0 pour qu'elle passe en tête.
2. **Les grands rendez-vous : fait le 26/11** (Agenda 1.1.4 installé, page n° 520 mise à jour).
   - Agenda 1.1.4 : le bloc « Prochains événements » peut se limiter à quelques thématiques cochées dans la colonne de droite (panneau « Thématiques affichées »). Aucune case cochée : tous les événements, un bouton par thématique présente (comportement de l'accueil, inchangé). Plusieurs cases : seuls ces événements, un bouton par thématique cochée qui a au moins un événement. Une seule case : pas de bouton. Titre facultatif au-dessus des cartes, lien « Tout l'agenda », message « Aucun rendez-vous n'est annoncé pour le moment dans cette sélection » quand il n'y a rien. Un événement classé dans plusieurs thématiques répond au bouton de chacune.
   - Page : l'agenda (attribut `thematiques: ["culture-et-patrimoine", "sport"]`, 9 événements) est placé juste après l'introduction, sous le titre « Les prochains rendez-vous » ; les accordéons mois par mois passent sous un titre « Les temps forts de l'année » ; la galerie ne bouge pas.
   - Thématiques (décision de Ju du 26/11) : « Culture et patrimoine » (déjà présente, n° 5) et « Sport » (créée, n° 16). Thématiques de la préprod : Animations et ateliers, Animations et fêtes, Culture et patrimoine, Sport, Vie citoyenne.
   - Classement : l'ancien site ne classait presque aucun événement. 4 événements ont été classés à la main en « Culture et patrimoine » (Exposition Trépid, Heure de la musique : Le Rêve, Le petit chaperon rouge qui voit tout noir, Après-midi cirque) ; ce classement est gardé dans `outils/migration/thematiques.py` pour la migration finale. Aucun événement sportif n'est annoncé : le bouton « Sport » et les boutons de choix n'apparaîtront qu'au premier événement classé en Sport. Après le 2/12, la page retombera sur le message « aucun rendez-vous » si aucun nouvel événement n'est classé : c'est aux agents de choisir la thématique à chaque événement saisi.
   - Effet de bord voulu : l'accueil et la page Agenda affichent maintenant des boutons « Tout / Culture et patrimoine / Vie citoyenne », puisque deux thématiques ont des événements à venir.
   - Tests : en local (WordPress 7.1.5, faux événements) 8 cas de réglage, boutons à la souris et au clavier, ordinateur et téléphone, axe-core 0 erreur, réglages dans l'éditeur ; sur la préprod, affichage ordinateur et téléphone contrôlé.
3. **Jumelage : fait le 26/11** (page n° 286). La section « Zoom sur nos villes jumelées » est devenue 4 rectangles identiques (bloc « Cartes de liens », 2 colonnes, icône Lieu) : Kahl am Main, Bitterfeld-Wolfen, Gremda, Salzano, chacun avec le pays, la date du jumelage et le texte du site actuel. Décision de Ju : pas de sous-page par ville (le site actuel n'a qu'une à deux phrases par ville), donc rectangles non cliquables ; le jour où la mairie fournit du contenu, il suffit de créer la page et de coller son adresse dans le rectangle. La section passe avant « Qu'est-ce que le jumelage ? ». Au passage : titres de niveau 3 et 4 mal hiérarchisés supprimés, noms propres corrigés dans les rectangles (Francfort, la Hesse, Saxe-Anhalt, Kahl am Main). Non repris : les 4 liens externes de l'ancienne page (kahl-main.de et wolfen.de, que je n'ai pas pu vérifier, et deux pages personnelles « pagespro-orange » pour Gremda et Salzano, très probablement fermées).
4. **Services municipaux : fait le 26/11** (page n° 221), en trois parties visibles dans « Accès rapide ».
   - « Contacter la mairie » : carte contact de l'hôtel de ville (adresse, téléphone, courriel, horaires). L'adresse et le téléphone étaient balisés comme des titres : c'est corrigé. Rappel « sur rendez-vous » et adresse unique contact.habitants@mairie-villefontaine.fr. Le visuel de l'hôtel de ville rénové (logos des financeurs) est gardé.
   - « L'annuaire des services » : les 11 accordéons du site actuel (coordonnées et horaires par service), gardés tels quels, puis la carte des services. La carte n'affiche encore que les 5 lieux de test.
   - « L'organigramme » : phrase d'attente (« sera publié ici prochainement »). Il n'existe pas sur le site actuel : à fournir par la mairie, puis à déposer dans cette partie (image avec description, ou PDF). **À retirer ou à remplir avant la mise en ligne.**
   - Retirés de la page : deux paragraphes sur l'histoire de la ville (présents sur la page Découvrir la ville), un bouton « + d'informations » qui n'avait pas de lien, un doublon de l'adresse de courriel.
   - Colonne latérale : l'encart ne répète plus les coordonnées de l'hôtel de ville, il affiche seulement l'adresse de courriel (« Une question, un signalement ? »).
5. Services publics : retirer l'avis périmé, 4 blocs (France Travail, La Poste, impôts, Pimms). À faire.
- Où c'est écrit : `outils/migration/recompositions.py` décrit ces pages à la main (il ne dépend pas de l'export de l'ancien site) ; `python3 recompositions.py` met à jour `pages.json`, et `construire.py` l'appelle. Sur la préprod, chaque page est créée ou mise à jour une par une par la route `migration` (`etape: pages`, `cle`), sans toucher au menu.
- Restent hors de ces 5 points : annuaire géolocalisé des 215 associations et catégories de la carte (aires de jeux, équipements sportifs, stationnement PMR, toilettes publiques, groupes scolaires, salles de spectacle, déchèteries, parkings, bâtiments municipaux, nature en ville), qui dépendent des données de la mairie.

## Images (règle décidée par Ju le 25/11)
- Règle : toute photo envoyée dans la médiathèque est convertie en WebP (miniatures comprises) et ramenée à 2 400 px de large au plus. Le fichier d'origine (JPEG ou PNG) est conservé sur le serveur, jamais servi aux visiteurs, sauf s'il pèse plus de 2 Mo : il est alors supprimé.
- La limite porte sur la largeur, pas sur le plus grand côté : une affiche en hauteur peut donc faire 2 400 px de large et davantage en hauteur.
- Code : `socle-villefontaine/includes/images.php` (constantes `VF_SOCLE_IMAGE_LARGEUR_MAX` = 2400 et `VF_SOCLE_ORIGINAL_POIDS_MAX` = 2 Mo). La conversion WebP n'est plus dans le thème : c'est une règle du site, pas d'apparence. Un rappel de la règle s'affiche sous la zone d'envoi de la médiathèque.
- Images déjà en médiathèque : route `/vf-deploiement/v1/images` du plugin Déploiement. `{simulation: true}` (valeur par défaut) liste sans rien toucher ; `{simulation: false, lot: 10}` traite par lots, à rappeler tant que `reste` est supérieur à 0 ; `{id: 123}` ne traite qu'une image. Trois opérations, toutes définitives : réduire sur place une image trop large (même nom, même adresse), refaire depuis son fichier d'origine une image qui avait été réduite plus que la règle ne le demande, supprimer un original de plus de 2 Mo.
- Fait sur la préprod le 25/11 : 64 images de 2 560 px ramenées à 2 400 px (aucune image ne dépasse plus 2 400 px), 4 originaux supprimés (P1110222.jpg 7,8 Mo, des-detectives-generes-par-ai… 4,2 Mo, affiche-Sorties-Familiales.jpg 2,1 Mo, vigilance_secheresse_affiche.png 2,0 Mo). 279 images au total.
- Photo du grand visuel de l'accueil (média n° 1835, `Vue-vaugelas-Villefontaine`) : envoyée par Ju pendant les 20 minutes où la limite était à 1 500 px, elle avait été réduite. Refaite depuis l'original : 1 800×1 350 px, c'est la taille du fichier fourni. Pour un rendu net sur les très grands écrans, il faudrait un fichier de 2 400 px de large.
- En attente de décision : 3 affiches en hauteur (Flyer_A4_Liste_Bons_Gestes…, deux CHECK-LIST A4) sont à 1 811 px de large parce que l'ancienne règle limitait le plus grand côté ; l'outil propose de les refaire à 2 400 px de large depuis leur original.
- À la mise en ligne : relancer la route en simulation sur la production après l'import des médias.

## Grand visuel de l'accueil (Socle 1.5.12, 25/11)
- À partir de 1 200 px de large, la photo se voit sur toute la largeur (opacité 0,96 au lieu de 0,78). Le voile bleu nuit se limite à un halo derrière le bloc de texte, une bande sous le menu et un léger fondu en bas ; il est nul sur la droite. En dessous de 1 200 px, rien n'a changé (le texte occupe presque toute la largeur).
- Les textes qui flottent sur la photo ont leur propre fond : « Info du jour » et les puces « Fréquent » sont sur un verre sombre (au lieu d'un verre clair), le surtitre est passé au vert clair #D7EE84.
- Grille : tracé blanc et fondu en dégradé d'origine (Ju y tient), seule l'opacité est montée de 0,18 à 0,6 : sur une photo lumineuse, à 0,18 elle ne se voyait plus.
- Historique des essais du 25/11, pour ne pas les refaire : la version 1.5.10 dégageait la droite mais assombrissait trop la gauche et rendait la grille invisible ; la version 1.5.11 traçait la grille en deux tons (clair et sombre), ce qui lui faisait perdre son effet de dégradé. Ju a refusé les deux.
- Contrastes des textes posés sur la photo (menu, info du jour, surtitre, titre, puces) calculés de 1 200 à 2 560 px : tous supérieurs à 5 pour 1 avec la photo actuelle (le menu était à 4,0 avant ces retouches). Calcul fait par simulation des couches, à revérifier si la photo change : le titre n'est protégé que par le halo.

## Plugin « Sécurité Villefontaine » (securite-villefontaine 1.1.0, 25/11)
Demandes de Ju : (1) fermer wp-admin et wp-login, connexion par /backoffice, blocage d'une adresse IP 1 heure après 3 échecs avec déblocage par l'administrateur ; (2) « mettre le paquet » sur la sécurité et habiller la page de connexion à la charte. Décision de Ju du 25/11 : pas de double authentification pour l'instant (si elle est ajoutée plus tard : obligatoire pour les administrateurs seulement).

### Connexion (includes/connexion.php, blocage.php)
- Adresse de connexion réglable (par défaut `backoffice`), wp-login.php et wp-admin masqués aux visiteurs.
- Blocage après 3 échecs pendant 1 heure (réglable), 24 heures en cas de récidive dans la journée. Un déblocage manuel remet le compteur à zéro. Le blocage ne concerne que la connexion : le site public reste accessible, les personnes connectées ne sont pas déconnectées. Message d'erreur identique que l'identifiant existe ou non.
- Déblocage : menu Sécurité > Vue d'ensemble, ou lien signé reçu par courriel (adresse d'administration du site, 5 courriels par heure au maximum).
- Adresses IP de confiance (jamais bloquées) : à remplir avec l'IP de la mairie, car tous les agents d'un bâtiment partagent en général une seule adresse publique.
- Mots de passe d'application soumis au même blocage ; liste des comptes masquée (API, pages auteur, plan de site).

### Comptes (includes/comptes.php)
- Mots de passe solides imposés à chaque création de compte ou changement de mot de passe : 12 caractères au moins, trois types de caractères (ou phrase de 20 caractères et plus), pas d'identifiant ni de mot courant (« azerty », « villefontaine »…). Les mots de passe déjà en place ne sont pas modifiés.
- Déconnexion automatique après 2 heures sans activité (réglable, désactivable). L'éditeur propose de se reconnecter sans perdre le texte en cours.
- Courriel au titulaire du compte à chaque connexion depuis un appareil inconnu (témoin technique `vf_appareil_…` déposé à la connexion, exempté de consentement car strictement nécessaire à la sécurité).
- « Mot de passe oublié » : 3 demandes par heure et par adresse IP, réponse identique que le compte existe ou non.

### Protection du site (includes/durcissement.php, fichiers.php)
- En-têtes de sécurité sur toutes les réponses : HTTPS imposé au navigateur pendant un an (HSTS), affichage dans un cadre externe interdit, type des fichiers non deviné, fonctions du navigateur inutilisées coupées, version de PHP retirée. Politique de contenu volontairement courte (cadres, formulaires, contenus actifs) pour ne rien casser.
- Version de WordPress remplacée par une empreinte dans l'adresse des fichiers ; liens techniques retirés de l'en-tête des pages.
- API de WordPress (/wp-json/) réservée aux personnes connectées. Restent publiques : les routes du site qui commencent par `vf-` (recherche, formulaires) et celles ajoutées dans Sécurité > Protection du site. Si une extension tierce a besoin d'une route publique, c'est là qu'on l'ajoute.
- Commentaires et rétroliens désactivés partout ; éditeur de fichiers de l'administration désactivé.
- Règles écrites en tête du fichier `.htaccess` (bloc `# BEGIN Securite Villefontaine`) : readme.html, license.txt, install.php, upgrade.php (sauf personne connectée), xmlrpc.php, rétroliens, envoi de commentaires, scripts internes de WordPress, tout fichier PHP appelé directement dans les médias, extensions et thèmes, notices des extensions, fichiers de sauvegarde. Ils répondent « page introuvable ». Exception prévue pour le mode invité de LiteSpeed Cache.
- Sécurité de l'écriture : après chaque écriture, le plugin appelle la page d'accueil ; si le serveur répond par une erreur, le fichier d'origine est remis aussitôt. Le bloc est retiré à la désactivation du plugin. Bouton « Réécrire et vérifier » dans l'onglet Protection du site (à utiliser après un changement d'hébergement).
- Attention : une extension tierce qui appellerait directement un de ses fichiers PHP par le navigateur serait bloquée par ces règles (c'est rare et déconseillé). En cas de doute, décocher « Fichiers techniques » pour tester.

### Journal (includes/journal.php)
- Table `wp_vf_securite_journal` : connexions, déconnexions, échecs, blocages, nouveaux appareils, mots de passe modifiés ou demandés, comptes créés ou supprimés, rôles modifiés, extensions installées, activées, désactivées ou supprimées, thème changé, réglages importants modifiés.
- Conservation : 180 jours par défaut (réglable de 30 à 365), purge quotidienne. Le journal contient des adresses IP et des identifiants : à inscrire au registre des traitements de la mairie et à mentionner dans la politique de confidentialité.

### Page de connexion (includes/habillage.php, assets/connexion.css)
- Écran partagé (choix de Ju) : photo du lac, logo blanc et message d'accueil à gauche ; formulaire à droite. Sur téléphone, la photo devient un bandeau.
- Titre, phrase d'accueil et photo modifiables dans Sécurité > Page de connexion (photo de la médiathèque, ou photo du lac fournie par défaut).
- Polices (Figtree, Unbounded), logo et photo embarqués dans le plugin : rien ne dépend du thème ni d'un autre site.
- Accessibilité : repères « en-tête » et « contenu principal », titre de page visible, focus visible, contrastes conformes ; axe-core 0 erreur sur 10 vues (connexion, erreur, mot de passe oublié, confirmation ; ordinateur, tablette, téléphone).

### Tests
- En local sur WordPress 7.1.5 : environ 70 scénarios (connexion, blocage, récidive, déblocage, mots de passe, inactivité, nouvel appareil, API, commentaires, administration, écriture et retour arrière du .htaccess, désactivation).
- Sur la préprod le 25/11 : règles .htaccess écrites et vérifiées, 12 protections actives, 25 adresses sensibles fermées, pages publiques, recherche, formulaire et carte toujours fonctionnels, administration et éditeur fonctionnels.
- Les règles .htaccess n'ont pas pu être testées en local (pas de serveur Apache dans l'environnement de travail) : elles ont été validées directement sur la préprod.

### À la mise en ligne
- Installer le plugin sur la production, communiquer l'adresse /backoffice aux agents, saisir l'IP de la mairie en adresse de confiance.
- Régler le SMTP : sans lui, ni les alertes ni les liens « mot de passe oublié » n'arrivent.
- Vérifier le réglage « lecture de l'adresse IP » si un CDN est placé devant le site.
- HSTS : une fois le site en production, le navigateur des visiteurs refusera le site en HTTP pendant un an. Le certificat HTTPS doit donc toujours être valide (renouvellement automatique chez Hostinger).
- Chez Hostinger (hors plugin) : sauvegardes quotidiennes, mises à jour automatiques, validation en deux étapes sur le compte hPanel.

## Préprod (https://preprod.villefontaine.fr)
- WordPress 7.1.5, PHP 8.4, LiteSpeed (compression brotli active, réponse 150 à 230 ms) ; « ne pas indexer » activé. Plugin LiteSpeed Cache non installé (à proposer à la mise en ligne).
- Permaliens : actualités en /actualites/titre/ (catégories /actualites/categorie/…). Agenda, kiosque et lieux ont leurs propres adresses (/agenda/, /kiosque/, /lieu/).
- Pages publiées de l'ancien site, Plan du site, Accessibilité du site ; méga-menu raccordé (photo de chaque rubrique = image mise en avant de la page de rubrique), redirections des anciennes adresses (pages, actualités, événements).
- 10 actualités et 167 événements publiés depuis le 1er janvier 2026 (règle de Ju : rien d'antérieur). Les faux événements de démonstration ont été supprimés.
- « Info du jour » de l'accueil (ex « Info flash », renommée le 24/11) : réglée dans le bloc du grand visuel (Pages > Accueil, panneau « Info du jour ») ; actuellement « Plan Vigipirate : niveau « urgence attentat » ».
- Blocs Socle : documents, chiffres, cartes de liens, carte contact, vidéo (avec consentement et transcription), trombinoscope, formulaire de contact (modèles « contact » et « signalement »), plan du site, « Dans cette rubrique » (sous-pages publiées), schéma interactif. Icône par page (panneau « Icône de la page »).
- Colonne latérale des pages : sommaire (intitulé « Accès rapide » depuis le 24/11), contact de la page (ou contact général de la mairie), autres pages de la rubrique.
- Icône du site (favicon) : favicon officiel repris de villefontaine.fr, média n° 1834, réglé dans Réglages > Général. C'est un réglage du site, pas du code : à refaire sur la production.

## Retouches du 24/11/2026 (demande de Ju)
- Typographie : toutes les tailles de texte réduites de 12 % (texte courant 17 → 15 px), titres de page (H1 des pages intérieures) réduits de 20 % (54 → 43 px sur grand écran). Plancher : aucun texte sous 12 px. Champs de formulaire maintenus à 16 px (en dessous, iOS zoome à la saisie). Le titre de l'accueil suit la règle des 12 %.
- Grand visuel de l'accueil : 710 → 652 px de haut à 1440 px (40 px de marges retirées + effet des textes plus petits).
- Kiosque : la différence avec la version de test venait des images (vignettes de partage Calaméo), pas du code. Le n° 120 du Mag n'est pas encore dans le kiosque de la préprod.
- Tuiles d'actualités (Actualités 1.0.5) : le voile bleu qui couvrait toute la photo est supprimé. Dégradé limité à la zone du titre, valeurs fournies par Ju : `linear-gradient(0deg, rgba(7, 30, 36, .94) 20%, rgba(7, 30, 36, .44) calc(100% - var(--fondu) - 8px), rgba(7, 30, 36, 0) 100%)`. Toute la tuile est cliquable.
- Méga-menu (ordinateur) : marges réduites et espaces supprimés entre les liens de 3e niveau, d'après la capture de Ju.

## Page « Faire un signalement » (24/11)
- Le bloc « Faire un signalement » du panneau « Vos démarches » pointait vers /signalement/, qui n'existait pas. Choix de Ju : créer une vraie page avec formulaire.
- Page /signalement/ (page n° 1832, gabarit pleine largeur), formulaire maison en modèle « signalement » : nature du signalement (6 catégories proposées), lieu obligatoire, objet du message construit automatiquement, lieu visible dans « Messages reçus ».
- À faire valider par la mairie : destinataires (adresse fictive `signalement@example.org` pour l'instant), liste des catégories, textes. Un message « Test YRSA » est resté dans « Messages reçus ».
- Idée pour une v2 : joindre une photo au signalement.

## Recherche dans l'en-tête (Socle 1.5.7)
- L'ancienne loupe lançait une recherche vide : elle est remplacée par un bouton qui ouvre un champ de recherche sur place, avec les suggestions prédictives (même moteur que l'accueil).
- Clavier : Échap referme et rend le focus au bouton ; le champ et le méga-menu ne peuvent pas être ouverts en même temps.
- Sur téléphone, la loupe est masquée (760 px et moins) : le champ de recherche est en haut du tiroir du menu.

## Bloc « Schéma interactif » (Socle 1.5.0) et page Démarches d'urbanisme
- Bloc générique `vf/schema-interactif` : une illustration, des points numérotés (position en % de l'image), une fiche par point (règles saisies une par ligne, « condition : dossier » ; une ligne commençant par # = intertitre), des sigles expliqués (pastille, bulle au survol ou au clavier, lien vers la démarche), un rappel, et la liste complète des fiches sous l'image. Tout se règle dans la colonne de droite de l'éditeur.
- Accessibilité : points = boutons, Échap referme et rend le focus, liste complète lisible sans JavaScript ; axe-core 0 erreur sur le bloc (ordinateur et téléphone, fiche ouverte ou fermée). Garder au moins 8 % de largeur d'image entre deux points (taille des cibles tactiles).
- Page /cadre-de-vie/urbanisme/demarches-urbanisme/ (page n° 449) : contenu du Word du service urbanisme (novembre 2026), 17 types de travaux. Les 5 définitions de l'ancienne page (certificat d'urbanisme, déclaration préalable, permis…) sont conservées en fin de page. L'ancienne version reste dans les révisions WordPress.
- Illustration : générée avec Higgsfield (GPT Image 2.6, 2 variantes, 4 crédits), déposée dans la médiathèque de la préprod (média n° 1829, `schema-autorisations-travaux.webp`, 1600×1195). Elle n'est pas dans le dépôt : si le site est reconstruit ailleurs, la remettre dans la médiathèque et la re-sélectionner dans le bloc (constante IMAGE_SCHEMA de urbanisme.py).
- Liens des sigles : pages service-public.gouv.fr (DPC → F17578, DPA → R1995, PC → R11637 cerfa 13406, PA → F17665, PD → R1980 cerfa 13405, DAACT → R1978 cerfa 13408, division de terrain → F37932). Choix : pointer vers les pages service-public plutôt que vers un PDF, car les numéros de cerfa des déclarations préalables ont été renouvelés en 2026 (16702, 16703).

## Recherche
- Accueil : le panneau « Vos démarches » ne s'ouvre que si une démarche correspond ; sinon recherche directe dans le site.
- Recherche prédictive (accueil, en-tête et page de résultats) : index léger servi par /wp-json/vf-socle/v1/index, recalculé à chaque publication ; synonymes du quotidien dans socle-villefontaine/assets/js/suggestions.js (liste SYNONYMES, facile à compléter).

## Vitesse (mesures Lighthouse mobile sur une copie locale compressée comme en production)
- Accueil 95, agenda 95, pages intérieures 99 ; accessibilité, bonnes pratiques et référencement 100. (Mesures du 20/11, à refaire après les retouches des 24 et 25/11.)
- Carte (Leaflet) chargée seulement à l'approche de l'écran ; titre de l'accueil sans fondu ; photo d'accueil allégée.

## Accessibilité (RGAA 4.1.2)
- Aucun widget ou surcouche d'accessibilité (exigence du cahier des charges, choix confirmé par Ju).
- Audit RGAA interne du 19/11/2026 sur 24 pages : 72 critères conformes, 1 non conforme (13.3 : documents PDF et kiosque Calaméo), 33 non applicables, soit 98,6 %. Déclaration en ligne « partiellement conforme », mention du pied de page à jour. Compte rendu client (Claude Docs) : https://claude.ai/artifact/4ups3STp489AiFjbuNYoxJ
- Corrections de l'audit (Socle, includes/accessibilite.php et blocs) : en-têtes de tableaux (scope automatique), images légendées (role group), attribut sizes sans srcset retiré, texte d'exemple de recherche plus foncé, carte en français et annonce du nombre de lieux, titres de vidéos et champ transcription, tableau décrivant le graphique du budget, jeton du formulaire renouvelé toutes les 4 h (aucune limite de temps), extrait vide masqué.
- PDF : 156 documents, 26 balisés, 130 non balisés dont 30 scans. Vidéo du jardin collectif publiée le 12/11/2019 (non soumise) ; vidéo The Village = contenu de tiers.
- axe-core : 0 erreur sur 24 pages types, en ordinateur et en mobile (avant le 24/11). Code HTML valide (validateur W3C). Navigation clavier, focus visible, 320 px, espacement du texte : conformes.
- À refaire après les retouches des 24 et 25/11 : passe axe-core sur les pages types (tailles de texte, nouvel en-tête avec recherche, tuiles d'actualités, grand visuel de l'accueil), sur la page Démarches d'urbanisme et sur la page Signalement en conditions réelles.
- Point de vigilance : sur les tuiles d'actualités, l'étiquette « Actualités » se trouve dans la partie claire du dégradé de Ju ; son contraste dépend de la photo. Proposition en attente : la placer sur une pastille sombre.
- Schéma pluriannuel 2027-2029 rédigé (document Claude Docs), éléments entre crochets à compléter par la mairie (référent, budget, prestataires, date de mise en ligne).
- Reste à faire pour « totalement conforme » : traiter les PDF (tri, liens vers les sources officielles, pages web, PDF accessibles) et les publications du kiosque ; description détaillée de la carte du grand projet centre-ville ; test complémentaire avec lecteurs d'écran (NVDA, VoiceOver).

## Formulaire de contact (choix de Ju : bloc maison plutôt que WPForms)
- Bloc « Formulaire de contact » : sans jQuery ni service tiers, erreurs accessibles (résumé + message par champ), consentement RGPD. Deux modèles : « contact » et « signalement ».
- Anti-spam : champ piège, jeton signé (3 s minimum, 6 h maximum, renouvelé automatiquement toutes les 4 h), 5 envois par 15 minutes et par IP (empreinte).
- 3 services d'exemple (adresses fictives en example.org) : vraies adresses et envoi SMTP à régler à la fin.
- Messages conservés dans l'admin (menu « Messages reçus ») et supprimés automatiquement après 12 mois.

## À régler à la mise en ligne
- Plugin LiteSpeed Cache, retrait du « ne pas indexer », SMTP (formulaires et alertes de sécurité), Search Console, Matomo (mode exempté CNIL).
- Sécurité : voir la liste « À la mise en ligne » du plugin Sécurité ci-dessus.
- Images : relancer la remise à la règle (simulation d'abord) après l'import des médias sur la production.
- Favicon à régler sur la production (réglage du site).

## Points ouverts
- Organigramme des services municipaux : à demander à la mairie (la page Services municipaux affiche une phrase d'attente, à retirer ou à remplir avant la mise en ligne).
- Contenus attendus de la mairie pour les 6 pages en brouillon : Joindre les élus, Plan canicule, Handicap, Relais emploi du CCAS, Infos Enedis et Semidao, La collecte.
- Pages à recomposer d'après le document d'architecture : Budget fait le 25/11, Les grands rendez-vous et Jumelage et Services municipaux faits le 26/11 ; reste Services publics, puis l'annuaire des associations et les catégories de la carte.
- Agenda : la liste des 12 thématiques est toujours attendue du service communication ; d'ici là, 5 thématiques existent et seuls quelques événements sont classés. À dire aux agents : choisir la thématique de chaque événement, sinon il n'apparaît pas sur la page des grands rendez-vous.
- Budget, à faire confirmer par la mairie : (1) l'année du graphique de répartition des dépenses repris dans « Les chiffres clés » : il n'est pas daté et ses montants (7,7, 6,5, 4,8, 4,1 et 3,4 millions d'euros) ne se retrouvent pas dans le rapport de présentation du budget 2024, il vaudrait mieux un visuel du dernier budget ; (2) le compte administratif 2022 n'est pas sur le site actuel (la page 2023 n'a que deux documents) ; (3) deux PDF sont très lourds et gagneraient à être allégés par la mairie : « Budget 2025 de la commune » (119 Mo) et « Compte administratif 2024 » (64 Mo).
- Photo du grand visuel de l'accueil : demander un fichier de 2 400 px de large (l'actuel fait 1 800 px).
- 3 affiches en hauteur à refaire à 2 400 px de large (décision de Ju attendue).
- Sécurité : IP de la mairie à obtenir pour les adresses de confiance (ou nombre d'essais plus élevé) ; double authentification écartée pour l'instant par Ju, à reproposer à la mise en ligne (c'est la mesure la plus efficace contre le vol de mot de passe).
- Page urbanisme, à faire confirmer par le service urbanisme : (1) serre : le schéma saute de « ≤ 5 m² » à « > 20 m² : DPC » (rien entre 5 et 20 m², et pas de seuil de permis) ; (2) panneaux photovoltaïques : « ≤ 250 kW : DPC » et « ≥ 250 kW : PC » se recouvrent à 250 kW ; (3) la phrase « Attention à ne pas abîmer la porte » du Word n'a pas été publiée (elle semble être là par erreur) ; (4) garder ou retirer les 5 définitions de l'ancienne page. Le document d'architecture note aussi « Revenir vers Julien » pour l'urbanisme.
- Page Signalement : destinataires, catégories et textes à valider par la mairie.
- 9 liens externes à vérifier par Ju (aappma, maipo, decouvertenaturecapi, solidarites-sante, ccinordisere, associationactiom, eaupotable, docs.info.apple.com, YouTube streams).
- Les lieux de la carte sur la préprod sont des fiches de test (« [Test] ») à remplacer par les vrais lieux.
- Import du fichier Excel des lieux, logo vectoriel, textes manquants, 12 thématiques (service communication).
