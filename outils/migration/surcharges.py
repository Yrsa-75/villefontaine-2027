# -*- coding: utf-8 -*-
"""Pages dont le contenu n'est pas dans Elementor (champs ACF) ou doit être recomposé à la main."""
import json, re, html
from convertir import j, esc, b_paragraphe, b_titre, b_documents, b_boutons, b_a_savoir, img_bloc, original

XML = '/mnt/user-data/uploads/SITE-2027/export/villefontaine.WordPress.2026-11-17.xml'
_xml = None

def meta_acf(slug):
    global _xml
    if _xml is None:
        _xml = open(XML, encoding='utf-8').read()
    i = _xml.find('<wp:post_name><![CDATA[%s]]>' % slug)
    s = _xml.rfind('<item>', 0, i); e = _xml.find('</item>', i)
    return dict(re.findall(r'<wp:meta_key><!\[CDATA\[(.*?)\]\]></wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]></wp:meta_value>', _xml[s:e], re.S))

MENTION = re.compile(r'\*?\s*(Conseill[eè]re? communautaire CAPI)\s*\*?', re.I)

def personne(nom, role='', photo=''):
    nom = re.sub(r'\s+', ' ', html.unescape(re.sub(r'<[^>]+>', '', nom or ''))).strip()
    role = re.sub(r'\s+', ' ', html.unescape(re.sub(r'<[^>]+>', ' ', role or ''))).strip()
    mention = ''
    for txt in (nom, role):
        m = MENTION.search(txt)
        if m:
            mention = m.group(1)[0].upper() + m.group(1)[1:]
    nom = MENTION.sub('', nom).strip(' *')
    role = MENTION.sub('', role).strip(' *-')
    if role:
        role = role[0].upper() + role[1:]
    p = {'nom': nom, 'fonction': role, 'mention': mention}
    if photo:
        p['image'] = photo
    return p

def b_trombi(titre, personnes, vedette=False, ancre=''):
    a = {'titre': titre, 'personnes': personnes}
    if vedette: a['vedette'] = True
    if ancre: a['anchor'] = ancre
    return f'<!-- wp:vf/trombinoscope {j(a)} /-->'

def elus(ATTACH):
    m = meta_acf('elus')
    def photo(k):
        v = (m.get(k) or '').strip()
        return original(ATTACH[int(v)]['url']) if v.isdigit() and int(v) in ATTACH else ''
    maire = personne(m.get('prenom_nom'), m.get('sous-texte'), 'https://villefontaine.fr/wp-content/uploads/2026/04/Patrick-Nicole-William3.webp')
    adj = [personne(m.get(f'prenom_et_nom_adjoint_{i}'), m.get(f'sous-titre_adjoint_{i}'), photo(f'photo_adjoint_{i}')) for i in range(1, 10)]
    dele = [personne(m.get(f'prenom_et_nom_conseiller_delegue_{i}'), m.get(f'sous-titre_conseiller_delegue_{i}'), photo(f'photo_conseiller_delegue_{i}')) for i in range(1, 9)]
    dele += [personne('Murat COLMEK', "En charge de l'accompagnement et de l'animation jeunesse", 'https://villefontaine.fr/wp-content/uploads/2026/04/Murat-COLMEK.webp'),
             personne('Sébastien CAMPO', 'En charge du numérique, des nouvelles technologies et de la cybersécurité', 'https://villefontaine.fr/wp-content/uploads/2026/04/Sebastien-CAMPO.webp')]
    # Sous-titres des conseillers tels qu'affichés sur l'ancien site (liste d'opposition).
    st = {10: 'sous-titre_conseiller_8_12', 11: 'sous-titre_conseiller_8_12', 12: 'sous-titre_conseiller_8_12', 13: 'sous-titre_conseiller_12'}
    cons = [personne(m.get(f'prenom_et_nom_conseiller_{i}'), m.get(st.get(i, ''), ''), photo(f'photo_conseiller_{i}')) for i in range(1, 14)]
    cons = [c for c in cons if c['nom']]
    return [
        b_paragraphe('Le conseil municipal de Villefontaine compte 33 élus. Il règle par ses délibérations les affaires de la commune : budget, projets, services publics.'),
        b_trombi('', [maire], True),
        b_trombi('Les adjoints', adj, ancre='adjoints'),
        b_trombi('Les conseillers municipaux délégués', dele, ancre='conseillers-delegues'),
        b_trombi('Les conseillers municipaux', cons, ancre='conseillers-municipaux'),
    ]

U = 'https://villefontaine.fr/wp-content/uploads/'
BUDGETS = [
    ('Budget 2026', True, [('Rapport d’orientation budgétaire 2026', '2026/03/PJ-Rapport-dOrientation-Budgetaire.pdf'), ('Maquette budgétaire : budget primitif 2026', '2026/05/Maquette-budgetaire-BP-2026.pdf'), ('Compte financier unique 2025', '2026/05/Maquette-budgetaire-CFU-2025.pdf')]),
    ('Budget 2025', False, [('Rapport d’orientation budgétaire 2025', '2025/03/PJ-RAPPORT-DORIENTATION-BUDGETAIRE-2025.pdf'), ('Budget 2025 de la commune', '2025/04/Budget-2025-de-la-commune.pdf'), ('Budget primitif 2025 : rapport de présentation', '2025/04/BP-2025-RAPPORT-DE-PRESENTATION.pdf'), ('Compte administratif 2024', '2025/04/Compte-administratif-2024.pdf'), ('Compte administratif 2024 : rapport de présentation', '2025/04/CA-2024-RAPPORT-DE-PRESENTATION.pdf')]),
    ('Budget 2024', False, [('Rapport d’orientation budgétaire 2024', '2024/02/RAPPORT-DORIENTATION-BUDGETAIRE-2024.pdf'), ('Budget primitif 2024', '2024/05/BUDGET-PRIMITIF-2024.pdf'), ('Budget primitif 2024 : rapport de présentation', '2024/05/BP-2024-RAPPORT-DE-PRESENTATION.pdf'), ('Compte administratif 2023', '2024/05/COMPTE-ADMINISTRATIF-2023.pdf'), ('Compte administratif 2023 : rapport de présentation', '2024/05/CA-2023-RAPPORT-DE-PRESENTATION.pdf')]),
    ('Budget 2023', False, [('Rapport d’orientation budgétaire 2023', '2024/02/RAPPORT-DORIENTATION-BUDGETAIRE-2023.pdf'), ('Budget primitif 2023', '2024/02/Budget-Primitif-2023-signe.pdf')]),
]

def details(titre, blocs, ouvert=False):
    attrs = (' ' + j({'showContent': True})) if ouvert else ''
    return f'<!-- wp:details{attrs} -->\n<details class="wp-block-details"{" open" if ouvert else ""}><summary>{esc(titre)}</summary>' + '\n\n'.join(blocs) + '</details>\n<!-- /wp:details -->'

def budget(ATTACH):
    out = [
        b_paragraphe('Chaque année, le conseil municipal débat des orientations budgétaires, vote le budget primitif (les prévisions de recettes et de dépenses) puis approuve le compte de l’année écoulée. Tous ces documents sont publics et consultables ci-dessous.'),
        img_bloc(U + '2024/07/Diagramme.png', 'Graphique circulaire de la répartition des dépenses de la ville, détaillé dans le tableau qui suit'),
        # Description détaillée du graphique (RGAA 1.6) sous forme de tableau.
        '<!-- wp:table -->\n<figure class="wp-block-table"><table class="has-fixed-layout"><caption>Répartition des dépenses présentée dans le graphique</caption><thead><tr><th scope="col">Domaine</th><th scope="col">Montant</th><th scope="col">Part</th></tr></thead><tbody>'
        '<tr><th scope="row">Éducation, familles</th><td>7,7 millions d’euros</td><td>29 %</td></tr>'
        '<tr><th scope="row">Gestion des ressources, services généraux, administration, sécurité</th><td>6,5 millions d’euros</td><td>24 %</td></tr>'
        '<tr><th scope="row">Culture, vie associative, action sociale, sport</th><td>4,8 millions d’euros</td><td>19 %</td></tr>'
        '<tr><th scope="row">Environnement, aménagement, cadre de vie</th><td>4,1 millions d’euros</td><td>15 %</td></tr>'
        '<tr><th scope="row">Autofinancement dégagé</th><td>3,4 millions d’euros</td><td>13 %</td></tr>'
        '</tbody></table></figure>\n<!-- /wp:table -->',
        b_titre('Documents budgétaires', 2),
    ]
    for titre, ouvert, docs in BUDGETS:
        out.append(details(titre, [b_documents([(t, U + u) for t, u in docs])], ouvert))
    return out

def marches(ATTACH):
    return [
        b_paragraphe('La Ville de Villefontaine publie ses consultations (appels d’offres et marchés à procédure adaptée) sur la plateforme AWS, Marchés publics info. Vous pouvez y consulter les avis, télécharger les dossiers de consultation et déposer votre offre en ligne.'),
        b_boutons([('Voir les consultations en cours', 'https://www.marches-publics.info/avis/index.cfm?IDS=522'), ('Accéder à son espace entreprise', 'https://www.marches-publics.info/fournisseurs.htm')]),
        b_a_savoir('Recevoir les nouveaux avis par courriel', [
            b_paragraphe('La plateforme AWS propose un service d’alerte gratuit sur les avis publiés par ses acheteurs publics et privés. Vous recevez chaque jour un courriel personnalisé si des avis correspondent à vos critères. L’inscription crée aussi votre espace entreprise, avec des services pour suivre vos appels d’offres.'),
            b_boutons([('S’inscrire aux alertes', 'http://www.aws-entreprises.com/inscription')]),
        ]),
    ]

SURCHARGES = {'elus': elus, 'budget': budget, 'marches-publics': marches}

def contact(ATTACH):
    from convertir import b_contact
    return [
        b_paragraphe('Une question, une demande ? Écrivez-nous avec le formulaire ci-dessous : votre message est transmis directement au service concerné. Vous pouvez aussi nous appeler ou venir à l’accueil de l’hôtel de ville.'),
        b_contact({'titre': 'Hôtel de ville', 'adresse': 'Place Pierre Mendès France\nBP 88, 38093 Villefontaine Cedex', 'tel': '04 74 96 00 00',
                   'horaires': 'Lundi, mardi, mercredi et vendredi : 8 h 30 à 12 h et 13 h 30 à 17 h\nJeudi : 10 h 30 à 12 h et 13 h 30 à 17 h\n1er samedi du mois : 8 h 30 à 12 h'}),
        b_a_savoir('Scolarité et enfance', [
            b_paragraphe('Pour toute demande concernant l’école, la restauration scolaire ou l’accueil de loisirs, passez par le Portail Famille : vos démarches y sont traitées plus vite.'),
            b_boutons([('Accéder au Portail Famille', 'https://villefontaine.portailfamille.net/')]),
        ]),
        '<!-- wp:vf/formulaire-contact ' + j({'destinataires': [{'libelle': 'Accueil de la mairie', 'courriel': 'accueil@example.org'}, {'libelle': 'État civil', 'courriel': 'etat-civil@example.org'}, {'libelle': 'Urbanisme', 'courriel': 'urbanisme@example.org'}]}) + ' /-->',
    ]

SURCHARGES['contact'] = contact

SURCHARGES['plan-site'] = lambda ATTACH: [b_paragraphe('Retrouvez ici toutes les pages du site. La liste se met à jour automatiquement.'), '<!-- wp:vf/plan-du-site /-->']


def accessibilite(ATTACH):
    from convertir import b_liste, b_details
    P = b_paragraphe
    L = lambda items: b_liste(items)
    return [
        P('La Ville de Villefontaine veut que son site soit utilisable par toutes et tous, quels que soient le matériel, le logiciel ou le handicap. Le site est conçu pour fonctionner avec les réglages de votre appareil : vous n’avez rien à installer.'),
        b_titre('Adapter l’affichage à vos besoins', 2),
        b_details('Agrandir le texte', [
            L(['<strong>Ordinateur</strong> : touches <kbd>Ctrl</kbd> et <kbd>+</kbd> pour agrandir, <kbd>Ctrl</kbd> et <kbd>-</kbd> pour réduire, <kbd>Ctrl</kbd> et <kbd>0</kbd> pour revenir à la normale (<kbd>Cmd</kbd> à la place de <kbd>Ctrl</kbd> sur Mac). Le site reste lisible jusqu’à un agrandissement de 400 %.',
               '<strong>iPhone, iPad</strong> : Réglages, Luminosité et affichage, Taille du texte.',
               '<strong>Android</strong> : Paramètres, Affichage, Taille de police (le chemin peut varier selon le modèle).']),
        ]),
        b_details('Renforcer les contrastes', [
            L(['<strong>Windows</strong> : Paramètres, Accessibilité, Thèmes de contraste.',
               '<strong>Mac</strong> : Réglages Système, Accessibilité, Affichage, Augmenter le contraste.',
               '<strong>iPhone, iPad</strong> : Réglages, Accessibilité, Affichage et taille du texte, Augmenter le contraste.',
               '<strong>Android</strong> : Paramètres, Accessibilité, Texte à contraste élevé.']),
        ]),
        b_details('Faire lire la page à voix haute', [
            L(['<strong>Navigateur Edge</strong> : bouton « Lecture à voix haute » ou touches <kbd>Ctrl</kbd>, <kbd>Maj</kbd> et <kbd>U</kbd>.',
               '<strong>Windows</strong> : le Narrateur se lance avec <kbd>Ctrl</kbd>, <kbd>Windows</kbd> et <kbd>Entrée</kbd>.',
               '<strong>Mac</strong> : VoiceOver se lance avec <kbd>Cmd</kbd> et <kbd>F5</kbd>.',
               '<strong>iPhone, iPad</strong> : Réglages, Accessibilité, Contenu énoncé (ou VoiceOver).',
               '<strong>Android</strong> : Paramètres, Accessibilité, Sélectionner pour prononcer (ou TalkBack).']),
        ]),
        b_details('Réduire les animations', [
            P('Si vous activez la réduction des animations sur votre appareil, le site la respecte automatiquement : plus aucun mouvement ni défilement animé.'),
            L(['<strong>Windows</strong> : Paramètres, Accessibilité, Effets visuels, Effets d’animation.',
               '<strong>Mac, iPhone, iPad</strong> : Accessibilité, Réduire les animations.',
               '<strong>Android</strong> : Paramètres, Accessibilité, Supprimer les animations.']),
        ]),
        b_details('Naviguer au clavier', [
            L(['<kbd>Tab</kbd> pour avancer d’un lien ou d’un bouton à l’autre, <kbd>Maj</kbd> et <kbd>Tab</kbd> pour revenir en arrière.',
               '<kbd>Entrée</kbd> pour ouvrir un lien ou activer un bouton, <kbd>Échap</kbd> pour fermer un menu ou une fenêtre.',
               'En haut de chaque page, les liens « Aller au contenu » et « Aller au menu » apparaissent à la première pression sur <kbd>Tab</kbd>.']),
        ]),
        b_titre('Déclaration d’accessibilité', 2),
        P('La Ville de Villefontaine s’engage à rendre ses sites internet, intranet, extranet et ses progiciels accessibles conformément à l’article 47 de la loi n° 2005-102 du 11 février 2005.'),
        P('Cette déclaration d’accessibilité s’applique au site <strong>villefontaine.fr</strong>.'),
        b_titre('État de conformité', 3),
        P('Le site villefontaine.fr est <strong>partiellement conforme</strong> avec le référentiel général d’amélioration de l’accessibilité (RGAA), version 4.1.2, en raison des non-conformités énumérées ci-dessous.'),
        b_titre('Résultats des tests', 3),
        P('L’audit de conformité réalisé le 19 novembre 2026 révèle que <strong>98,6 % des critères du RGAA version 4.1.2 sont respectés</strong> : sur les 73 critères applicables au site, 72 sont conformes et 1 n’est pas conforme. Les 33 autres critères ne s’appliquent pas aux contenus du site.'),
        b_titre('Contenus non accessibles', 3),
        b_titre('Non-conformités', 4),
        L(['Documents à télécharger (critère 13.3) : la plupart des documents PDF ne sont pas accessibles, notamment les documents budgétaires, les convocations du conseil municipal et certains formulaires ; une partie sont des documents numérisés (scans). Sur simple demande, nous vous transmettons leur contenu sous une forme adaptée.',
           'Publications du kiosque (critère 13.3) : le magazine municipal et les autres publications sont consultables avec le service Calaméo, sans version accessible pour le moment.']),
        b_titre('Dérogations pour charge disproportionnée', 4),
        P('Aucune.'),
        b_titre('Contenus non soumis à l’obligation d’accessibilité', 4),
        L(['La vidéo « Le jardin collectif de Vaugelas », publiée le 12 novembre 2019 (média temporel préenregistré publié avant le 23 septembre 2020).',
           'Les contenus de tiers qui ne sont ni financés ni développés par la Ville : vidéo « The Village » (Compagnie de Phalsbourg), arrêtés préfectoraux, formulaires Cerfa, lecteur de publications Calaméo.',
           'La carte interactive des services : les informations essentielles sont données sous forme de liste accessible à côté de la carte.']),
        b_titre('Établissement de cette déclaration', 3),
        P('Cette déclaration a été établie le 19 novembre 2026. Elle repose sur un audit réalisé en interne par YRSA, prestataire chargé de la refonte du site.'),
        P('Technologies utilisées pour la réalisation du site : HTML5, CSS, JavaScript, WAI-ARIA, SVG.'),
        P('Environnement de test : navigateur Chromium (moteur de Google Chrome et Microsoft Edge) sous Windows, sur ordinateur (1 280 pixels de large) et sur mobile (390 et 320 pixels), avec vérification de l’arbre d’accessibilité restitué aux technologies d’assistance. Des tests complémentaires avec les lecteurs d’écran NVDA et VoiceOver sont prévus.'),
        P('Outils utilisés : axe-core 4.10, validateur HTML du W3C, Lighthouse, mesure des contrastes de couleurs, navigation complète au clavier, test de l’espacement du texte et de l’affichage à 320 pixels.'),
        P('Pages du site ayant fait l’objet de la vérification de conformité :'),
        L(['Accueil : <code>villefontaine.fr/</code>', 'Contact : <code>villefontaine.fr/contact/</code>', 'Mentions légales : <code>villefontaine.fr/mentions-legales/</code>', 'Accessibilité du site : <code>villefontaine.fr/accessibilite-du-site/</code>', 'Plan du site : <code>villefontaine.fr/plan-du-site/</code>', 'Résultats de recherche : <code>villefontaine.fr/?s=carte+identite</code>', 'La mairie (rubrique) : <code>villefontaine.fr/la-mairie/</code>', 'Vos démarches : <code>villefontaine.fr/demarches/</code>', 'État civil : <code>villefontaine.fr/demarches/etat-civil/</code>', 'Réserver une salle : <code>villefontaine.fr/demarches/reserver-une-salle/</code>', 'Les élus : <code>villefontaine.fr/la-mairie/conseil-municipal/les-elus/</code>', 'Budget de la commune : <code>villefontaine.fr/la-mairie/budget/</code>', 'Ville vigilante : <code>villefontaine.fr/cadre-de-vie/tranquillite/ville-vigilante/</code>', 'Jardins et espaces verts : <code>villefontaine.fr/sortir-bouger/jardins-espaces-verts/</code>', 'Actualités : <code>villefontaine.fr/actualites/</code>', 'Une actualité : <code>villefontaine.fr/actualites/mouvement-de-greve-mardi-17-novembre/</code>', 'Agenda : <code>villefontaine.fr/agenda/</code>', 'Un événement : <code>villefontaine.fr/agenda/apres-midi-cirque/</code>', 'Carte des services : <code>villefontaine.fr/carte-des-services/</code>', 'Fiche d’un lieu de la carte des services', 'Kiosque : <code>villefontaine.fr/kiosque/</code>', 'Une publication du kiosque : <code>villefontaine.fr/kiosque/le-mag-n119/</code>', 'Données personnelles : <code>villefontaine.fr/donnees-personnelles/</code>', 'Page d’erreur (page introuvable)']),
        b_titre('Retour d’information et contact', 3),
        P('Si vous n’arrivez pas à accéder à un contenu ou à un service, contactez-nous pour être orienté vers une alternative accessible ou obtenir le contenu sous une autre forme :'),
        L(['par le <a href="/contact/">formulaire de contact</a> ;',
           'par téléphone au 04 74 96 00 00 ;',
           'par courrier : Mairie de Villefontaine, Place Pierre Mendès France, BP 88, 38093 Villefontaine Cedex.']),
        b_titre('Voies de recours', 3),
        P('Si vous avez signalé un défaut d’accessibilité qui vous empêche d’accéder à un contenu ou à un service du site et que vous n’avez pas obtenu de réponse satisfaisante, vous pouvez :'),
        L(['écrire un message au <a href="https://formulaire.defenseurdesdroits.fr/">Défenseur des droits</a> ;',
           'contacter <a href="https://www.defenseurdesdroits.fr/carte-des-delegues">le délégué du Défenseur des droits dans votre région</a> ;',
           'envoyer un courrier par la poste (gratuit, ne pas mettre de timbre) : Défenseur des droits, Libre réponse 71120, 75342 Paris CEDEX 07.']),
    ]

SURCHARGES['x-accessibilite-du-site'] = accessibilite

# Démarches d'urbanisme : contenu du service urbanisme (novembre 2026) avec le schéma interactif des autorisations de travaux.
from urbanisme import demarches_urbanisme
SURCHARGES['demarches-urbanisme'] = demarches_urbanisme

# Signalement : nouvelle page avec le formulaire au modèle « signalement ».
from signalement import signalement
SURCHARGES['x-signalement'] = signalement
