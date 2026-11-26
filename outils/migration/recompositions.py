# -*- coding: utf-8 -*-
"""
Pages recomposées à la main pour suivre le document « ARCHITECTURE SITE NEW » (25/11/2026) : les passages en bleu
du document décrivent ce que chaque page doit contenir.

Ces pages ne viennent pas d'une conversion automatique de l'ancien site : leur contenu est décrit ici, puis appliqué
au lot pages.json. Le fichier ne dépend pas de l'export XML de l'ancien site :
- python3 recompositions.py : met à jour pages.json (pages remplacées ou ajoutées, repérées par leur clé) ;
- construire.py l'appelle aussi, pour qu'une reconstruction complète du lot ne perde pas ces pages.
"""
import json
from convertir import j, esc, b_paragraphe, b_titre, b_documents, b_chiffres

DOCS = 'https://villefontaine.fr/wp-content/uploads/'


def page(cle, titre, slug, parent, ordre, extrait, blocs, icone='doc', image='', statut='publish', gabarit=''):
    return {'cle': cle, 'titre': titre, 'slug': slug, 'parent': parent, 'ordre': ordre, 'extrait': extrait,
            'contenu': '\n\n'.join(b for b in blocs if b), 'image': image, 'gabarit': gabarit, 'contact': {},
            'statut': statut, 'icone': icone}


def b_sous_pages(titre):
    return f'<!-- wp:vf/sous-pages {j({"titre": titre, "lock": {"move": False, "remove": True}})} /-->'


def b_liste_definitions(items):
    """Liste à puces « terme : explication » (le terme en gras)."""
    lis = ''.join(f'<!-- wp:list-item -->\n<li><strong>{esc(t)}</strong> : {d}</li>\n<!-- /wp:list-item -->\n\n' for t, d in items)
    return '<!-- wp:list -->\n<ul class="wp-block-list">' + lis.rstrip('\n') + '</ul>\n<!-- /wp:list -->'


def b_image(url, alt):
    jeton = 'VFIMG' + __import__('hashlib').md5(url.encode()).hexdigest()[:10]
    return (f'<!-- wp:image {{"id":{jeton},"sizeSlug":"large","linkDestination":"none"}} -->\n'
            f'<figure class="wp-block-image size-large"><img src="{url}" alt="{esc(alt)}" class="wp-image-{jeton}"/></figure>\n<!-- /wp:image -->')


# ---------------------------------------------------------------------------
# 1. Le budget de la commune : 6 rectangles (une page par année + les chiffres clés)
# ---------------------------------------------------------------------------
# Documents publiés par la mairie sur le site actuel, classés par année budgétaire. Le compte de l'année précédente
# (compte administratif, devenu compte financier unique) est voté en même temps que le budget : il est rangé avec lui.
BUDGETS = [
    (2026, [
        ('Rapport d’orientation budgétaire 2026', '2026/03/PJ-Rapport-dOrientation-Budgetaire.pdf'),
        ('Budget primitif 2026 : maquette budgétaire', '2026/05/Maquette-budgetaire-BP-2026.pdf'),
        ('Compte financier unique 2025', '2026/05/Maquette-budgetaire-CFU-2025.pdf'),
    ]),
    (2025, [
        ('Rapport d’orientation budgétaire 2025', '2025/03/PJ-RAPPORT-DORIENTATION-BUDGETAIRE-2025.pdf'),
        ('Budget 2025 de la commune', '2025/04/Budget-2025-de-la-commune.pdf'),
        ('Budget primitif 2025 : rapport de présentation', '2025/04/BP-2025-RAPPORT-DE-PRESENTATION.pdf'),
        ('Compte administratif 2024', '2025/04/Compte-administratif-2024.pdf'),
        ('Compte administratif 2024 : rapport de présentation', '2025/04/CA-2024-RAPPORT-DE-PRESENTATION.pdf'),
    ]),
    (2024, [
        ('Rapport d’orientation budgétaire 2024', '2024/02/RAPPORT-DORIENTATION-BUDGETAIRE-2024.pdf'),
        ('Budget primitif 2024', '2024/05/BUDGET-PRIMITIF-2024.pdf'),
        ('Budget primitif 2024 : rapport de présentation', '2024/05/BP-2024-RAPPORT-DE-PRESENTATION.pdf'),
        ('Compte administratif 2023', '2024/05/COMPTE-ADMINISTRATIF-2023.pdf'),
        ('Compte administratif 2023 : rapport de présentation', '2024/05/CA-2023-RAPPORT-DE-PRESENTATION.pdf'),
    ]),
    (2023, [
        ('Rapport d’orientation budgétaire 2023', '2024/02/RAPPORT-DORIENTATION-BUDGETAIRE-2023.pdf'),
        ('Budget primitif 2023', '2024/02/Budget-Primitif-2023-signe.pdf'),
    ]),
    # Documents 2022 : présents dans la médiathèque du site actuel mais plus affichés sur la page Budget.
    (2022, [
        ('Rapport d’orientation budgétaire 2022', '2022/04/RAPPORT-DORIENTATION-BUDGETAIRE-2022.pdf'),
        ('Budget primitif 2022', '2022/08/BP-2022-SIGNE.pdf'),
        ('Compte administratif 2021', '2022/08/CA-2021-SIGNE.pdf'),
    ]),
]


def resume_annee(annee, docs):
    """Chapô d'une page annuelle, selon les documents réellement disponibles."""
    titres = ' '.join(t for t, _ in docs).lower()
    morceaux = []
    if 'orientation' in titres:
        morceaux.append('orientations budgétaires')
    if 'budget' in titres:
        morceaux.append('budget voté')
    if 'compte' in titres:
        morceaux.append(f'compte de l’année {annee - 1}')
    phrase = ', '.join(morceaux[:-1]) + (' et ' if len(morceaux) > 1 else '') + morceaux[-1]
    return phrase[0].upper() + phrase[1:] + '.'


def pages_budget():
    pages = [page(
        'budget', 'Budget de la commune', 'budget', 'la-mairie', 3,
        'Pour bien comprendre le budget et les investissements de la ville.',
        [
            b_paragraphe('Chaque année, le conseil municipal débat des orientations budgétaires, vote le budget (les prévisions de recettes et de dépenses), puis approuve le compte de l’année écoulée. Tous ces documents sont publics : retrouvez-les ici, année par année.'),
            b_sous_pages('Consulter le budget'),
            b_titre('Comprendre les documents budgétaires', 2),
            b_liste_definitions([
                ('Le rapport d’orientation budgétaire', 'présenté au conseil municipal avant le vote du budget, il décrit la situation financière de la commune, ses priorités et ses projets pour l’année à venir.'),
                ('Le budget primitif', 'voté par le conseil municipal, il prévoit et autorise les recettes et les dépenses de l’année, pour le fonctionnement des services comme pour les investissements.'),
                ('Le compte administratif', 'il fait le bilan de l’année écoulée, avec ce qui a été réellement dépensé et encaissé. À partir des comptes de l’année 2025, il est remplacé par le compte financier unique.'),
            ]),
        ],
        icone='citoyen', image=DOCS + '2021/04/budget-participatif-freepik-scaled.jpg',
    )]
    for rang, (annee, docs) in enumerate(BUDGETS, start=1):
        pages.append(page(
            f'budget-{annee}', f'Budget {annee}', f'budget-{annee}', 'budget', rang, resume_annee(annee, docs),
            [
                b_paragraphe(f'Les documents du budget {annee} de la commune, tels qu’ils ont été présentés au conseil municipal.'),
                b_documents([(t, DOCS + u) for t, u in docs]),
                b_paragraphe('Un doute sur le rôle de chaque document ? Ils sont expliqués sur la page <a href="/la-mairie/budget/">Budget de la commune</a>.'),
            ],
            icone='calendrier',
        ))
    # Chiffres clés : la répartition des dépenses publiée par la mairie sur le site actuel (graphique non daté).
    repartition = [
        ('29 %', 'Éducation, familles : 7,7 millions d’euros'),
        ('24 %', 'Gestion des ressources, services généraux, administration, sécurité : 6,5 millions d’euros'),
        ('19 %', 'Culture, vie associative, action sociale, sport : 4,8 millions d’euros'),
        ('15 %', 'Environnement, aménagement, cadre de vie : 4,1 millions d’euros'),
        ('13 %', 'Autofinancement dégagé : 3,4 millions d’euros'),
    ]
    pages.append(page(
        'budget-chiffres-cles', 'Les chiffres clés', 'chiffres-cles', 'budget', len(BUDGETS) + 1,
        'La répartition des dépenses de la ville, en un coup d’œil.',
        [
            b_paragraphe('École, cadre de vie, culture, sport, action sociale : voici comment les dépenses de la ville se répartissent entre ses grandes missions.'),
            b_titre('La répartition des dépenses', 2),
            b_chiffres([{'nombre': n, 'libelle': l} for n, l in repartition]),
            b_image(DOCS + '2024/07/Diagramme.png', 'Graphique circulaire de la répartition des dépenses de la ville. Les parts et les montants sont ceux indiqués juste au-dessus.'),
            b_paragraphe('Pour le détail, consultez les documents de chaque année sur la page <a href="/la-mairie/budget/">Budget de la commune</a>.'),
        ],
        icone='info',
    ))
    return pages


# ---------------------------------------------------------------------------
# 2. Les grands rendez-vous sportifs et culturels : agenda de la page, par catégorie
# ---------------------------------------------------------------------------
# Thématiques de l'agenda affichées sur la page (slugs) : un bouton par thématique permet de choisir.
# Décision de Ju du 26/11/2026 : « Culture et patrimoine » (déjà présente dans l'agenda) et « Sport ».
THEMES_GRANDS_RDV = ['culture-et-patrimoine', 'sport']


def b_agenda_selection(titre, themes, nombre=9):
    return f'<!-- wp:vf/agenda-prochains {j({"format": "cartes", "nombre": nombre, "titre": titre, "thematiques": themes})} /-->'


def recompose_grands_rdv(p):
    """Place l'agenda (sport et culture) juste après l'introduction et donne un titre aux temps forts de l'année.
    Le texte, les accordéons et les photos viennent de la conversion de l'ancien site : ils ne sont pas réécrits."""
    import re
    contenu = re.sub(r'\s*<!-- wp:vf/agenda-prochains [^>]*?/-->', '', p['contenu'])
    contenu = contenu.replace('<!-- wp:heading -->\n<h2 class="wp-block-heading">Les temps forts de l’année</h2>\n<!-- /wp:heading -->\n\n', '')
    debut = contenu.find('<!-- wp:details')
    if debut < 0:
        return p
    intro, suite = contenu[:debut].rstrip(), contenu[debut:]
    p['contenu'] = '\n\n'.join([intro, b_agenda_selection('Les prochains rendez-vous', THEMES_GRANDS_RDV), b_titre('Les temps forts de l’année', 2), suite])
    return p


# ---------------------------------------------------------------------------
# 3. Jumelage : 4 rectangles, un par ville jumelle (sans sous-page : décision de Ju du 26/11/2026)
# ---------------------------------------------------------------------------
# Textes repris de la page du site actuel (« Zoom sur nos villes jumelées »), fautes de noms propres corrigées
# (Francfort, la Hesse, Saxe-Anhalt, Kahl am Main). Pas de lien : le site actuel n'a pas d'autre contenu par ville.
VILLES_JUMELLES = [
    ('Kahl am Main', 'Allemagne, jumelée depuis 1981. Premier partenaire de Villefontaine. Kahl am Main est situé à 30 kilomètres de Francfort, à la limite entre la Hesse et la Bavière, dans une région d’étangs et de forêts.'),
    ('Bitterfeld-Wolfen', 'Allemagne, jumelée depuis le 23 avril 1994. Notre second jumelage allemand s’est fait afin de développer les échanges scolaires pour les élèves du secondaire, ce qui n’est pas possible à Kahl am Main, dépourvu de lycées. Située à 30 km de Leipzig, dans le Land de Saxe-Anhalt, Wolfen s’honore d’un passé qui remonte à 600 ans.'),
    ('Gremda', 'Tunisie, jumelée depuis le 6 novembre 1994. La ville est située à une dizaine de kilomètres au nord-ouest de Sfax, dans la région qu’on appelle le Sahel. Depuis la signature du jumelage avec Villefontaine, plusieurs échanges scolaires ont déjà eu lieu.'),
    ('Salzano', 'Italie, jumelée depuis le 16 mai 2009. Ce jumelage a été conclu dans une volonté de consolider la connaissance et l’amitié entre les deux communautés. Salzano fait partie de la ville métropolitaine de Venise, en Vénétie.'),
]


def b_cartes(cartes, colonnes=2, niveau=3):
    """Rectangles « Cartes de liens ». Sans adresse, la carte n'est pas cliquable."""
    return f'<!-- wp:vf/cartes-liens {j({"cartes": cartes, "colonnes": colonnes, "niveau": niveau})} /-->'


def recompose_jumelage(p):
    """Remplace la partie « Zoom sur nos villes jumelées » (titres mal hiérarchisés) par 4 rectangles identiques,
    placés avant « Qu'est-ce que le jumelage ? ». Le reste du texte converti de l'ancien site est gardé tel quel."""
    import re
    c = re.sub(r'\s*<!-- wp:vf/cartes-liens [^>]*?/-->', '', p['contenu'])
    zoom = '<!-- wp:heading -->\n<h2 class="wp-block-heading">Zoom sur nos villes jumelées</h2>\n<!-- /wp:heading -->'
    quoi = '<!-- wp:heading -->\n<h2 class="wp-block-heading">Qu’est-ce que le jumelage ?</h2>'
    if zoom not in c or quoi not in c:
        return p
    avant_zoom, apres_zoom = c.split(zoom, 1)
    if avant_zoom.find(quoi) >= 0:
        # Première application : l'ancien « Zoom » (en fin de page) est supprimé, ses textes sont dans les rectangles.
        histoire, definition = avant_zoom.split(quoi, 1)
        definition = quoi + definition
    else:
        # Déjà recomposée : on garde l'histoire et la définition, on refait seulement les rectangles.
        histoire, definition = avant_zoom, quoi + apres_zoom.split(quoi, 1)[1]
    cartes = [{'titre': t, 'texte': x, 'url': '', 'icone': 'lieu'} for t, x in VILLES_JUMELLES]
    p['contenu'] = '\n\n'.join([histoire.strip(), zoom, b_cartes(cartes), definition.strip()])
    return p


# ---------------------------------------------------------------------------
# 4. Les services municipaux : contacts, annuaire, organigramme
# ---------------------------------------------------------------------------
COURRIEL_MAIRIE = 'contact.habitants@mairie-villefontaine.fr'
HOTEL_DE_VILLE = {
    'titre': 'Hôtel de ville',
    'texte': '',
    'adresse': 'Place Pierre Mendès France\nBP 88 - 38093 Villefontaine Cedex',
    'tel': '04 74 96 00 00',
    'courriel': COURRIEL_MAIRIE,
    'lien': '',
    'horaires': 'Lundi, mardi, mercredi et vendredi : 8 h 30 à 12 h et 13 h 30 à 17 h\nJeudi : 10 h 30 à 12 h et 13 h 30 à 17 h\n1er samedi du mois : 8 h 30 à 12 h',
}
# Texte d'attente : l'organigramme n'existe pas sur le site actuel, il doit être fourni par la mairie.
ORGANIGRAMME_ATTENTE = 'L’organigramme des services municipaux sera publié ici prochainement.'


def recompose_services_municipaux(p):
    """Trois parties (document du 25/11/2026) : contacter la mairie, l'annuaire des services, l'organigramme.
    Les accordéons des services, l'image de l'hôtel de ville et la carte viennent de la conversion de l'ancien site :
    ils sont gardés tels quels. L'adresse et le téléphone n'étaient pas des titres : ils passent dans une carte contact.
    Les deux paragraphes sur l'histoire de la ville sont retirés (ils sont sur la page « Découvrir la ville »)."""
    import re
    c = p['contenu']
    if 'wp:details' not in c:
        return p
    image = re.search(r'<!-- wp:image .*?<!-- /wp:image -->', c, re.S)
    accordeons = re.findall(r'<!-- wp:details -->.*?<!-- /wp:details -->', c, re.S)
    carte = re.search(r'<!-- wp:vf/carte-services [^>]*?/-->', c)
    # Retouche faite par Ju dans l'éditeur le 26/11/2026 : les horaires et la mention « sur rendez-vous » sont
    # rappelés dans l'accordéon « Accueil de l'hôtel de ville ».
    ancien = "<p>L'hôtel de ville est ouvert au public aux jours et horaires habituels.</p>\n<!-- /wp:paragraph -->"
    nouveau = ("<p>L'hôtel de ville est ouvert au public aux jours et horaires habituels :</p>\n<!-- /wp:paragraph -->\n\n"
               "<!-- wp:paragraph -->\n<p>Accueil physique le lundi, mardi, mercredi et vendredi de 8h30 à 12h et de 13h30 à 17h. Le jeudi de 10h30 à 12h et 13h30 à 17h. Ouverture le 1<sup>er</sup> samedi du mois de 8h30 à 12h. </p>\n<!-- /wp:paragraph -->\n\n"
               "<!-- wp:paragraph -->\n<p><strong>Les services accueillent le public uniquement sur rendez-vous.</strong></p>\n<!-- /wp:paragraph -->")
    accordeons = [x.replace(ancien, nouveau) for x in accordeons]
    blocs = [
        b_titre('Contacter la mairie', 2),
        f'<!-- wp:vf/contact-carte {j(HOTEL_DE_VILLE)} /-->',
        b_paragraphe('<strong>Les services accueillent le public uniquement sur rendez-vous.</strong>'),
        b_paragraphe(f'Pour toute question, information ou signalement, une seule adresse : <a href="mailto:{COURRIEL_MAIRIE}">{COURRIEL_MAIRIE}</a>'),
        image.group(0) if image else '',
        b_titre('L’annuaire des services', 2),
        b_paragraphe('Les coordonnées et les horaires de chaque service. Cliquez sur un service pour afficher ses informations.'),
        '\n\n'.join(accordeons),
        carte.group(0) if carte else '',
        b_titre('L’organigramme', 2),
        b_paragraphe(ORGANIGRAMME_ATTENTE),
    ]
    p['contenu'] = '\n\n'.join(b for b in blocs if b)
    # Colonne latérale : seulement l'adresse de courriel (les coordonnées complètes sont dans la page).
    p['contact'] = {'titre': 'Une question, un signalement ?', 'adresse': '', 'tel': '', 'courriel': COURRIEL_MAIRIE, 'lien': '', 'horaires': ''}
    return p


# ---------------------------------------------------------------------------
# 5. Services publics : une seule page avec France Travail, La Poste, les impôts et le Pimms
# ---------------------------------------------------------------------------
# Coordonnées relevées le 26/11/2026, à faire valider par la mairie :
# - France Travail : francetravail.fr (page de l'agence de Villefontaine) ;
# - La Poste : localisateur de bureaux de La Poste (labanquepostale.fr), horaires du bureau des Dauphins ;
# - impôts : adresse reprise d'un annuaire non officiel de l'administration, numéro national des impôts des particuliers ;
# - Pimms : coordonnées déjà publiées par la mairie sur le site actuel.
def b_contact(titre, **champs):
    d = {'titre': titre, 'texte': '', 'adresse': '', 'tel': '', 'courriel': '', 'lien': '', 'horaires': ''}
    d.update(champs)
    return f'<!-- wp:vf/contact-carte {j(d)} /-->'


def recompose_services_publics(p):
    """L'ancienne page ne parlait que du Pimms (avis de fermeture d'août, dates d'avril, titres utilisés comme du texte).
    Quatre parties désormais ; les accordéons et les documents du Pimms viennent de la conversion de l'ancien site."""
    import re
    c = p['contenu']
    accordeons = re.findall(r'<!-- wp:details -->.*?<!-- /wp:details -->', c, re.S)
    documents = re.search(r'<!-- wp:vf/documents [^>]*?/-->', c)
    if not accordeons:
        return p
    # Le Pimms mobile : la liste des dates d'avril est périmée, la tournée à jour est dans le document joint.
    accordeons = [re.sub(r'\s*<!-- wp:list -->\s*<ul class="wp-block-list"><!-- wp:list-item -->\s*<li>Mardi 1er avril</li>.*?<!-- /wp:list -->', '', a, flags=re.S) for a in accordeons]
    blocs = [
        b_paragraphe('Emploi, courrier, impôts, démarches du quotidien : les principaux services publics présents à Villefontaine, avec leurs coordonnées.'),

        b_titre('France Travail', 2),
        b_paragraphe('France Travail s’occupe principalement des dossiers de demande d’emploi et du versement des allocations de chômage. L’agence propose un accompagnement personnalisé vers l’emploi et des ateliers collectifs thématiques (CV, lettre de motivation, etc.).'),
        b_contact('Agence France Travail de Villefontaine', texte='Employeurs : composez le 3995.',
                  adresse='315 rue Serge Mauroit\n38090 Villefontaine', tel='3949',
                  horaires='Sans rendez-vous : du lundi au vendredi, de 8 h 30 à 12 h 30\nSur rendez-vous : lundi, mardi et mercredi de 12 h 30 à 16 h 30, vendredi de 12 h 30 à 15 h 30',
                  lien='https://www.francetravail.fr/annuaire/votre-agence-francetravail/villefontaine-38090'),

        b_titre('La Poste', 2),
        b_paragraphe('Deux points de contact La Poste vous accueillent à Villefontaine.'),
        b_contact('Bureau de poste Villefontaine Les Dauphins', adresse='Place de l’Hôtel de Ville\n38090 Villefontaine',
                  horaires='Du lundi au vendredi : 9 h à 12 h 30 et 14 h à 17 h 30\nSamedi : 9 h à 12 h',
                  lien='https://www.labanquepostale.fr/particulier/localisation_bureau/resultat_localisation_bureau.detail.html/villefontaine-lesdauphins-385530.html'),
        b_contact('Agence postale communale', adresse='601 chaussée des Escoffiers\n38090 Villefontaine', lien='https://www.laposte.fr/bureaux-poste'),

        b_titre('Impôts', 2),
        b_paragraphe('Le service des impôts des particuliers est installé au centre des finances publiques, place Charles-de-Gaulle. La plupart des démarches peuvent aussi se faire en ligne, depuis votre espace particulier sur impots.gouv.fr.'),
        b_contact('Service des impôts des particuliers', adresse='Centre des finances publiques\nPlace Charles-de-Gaulle\n38097 Villefontaine Cedex',
                  tel='0 809 401 401', lien='https://www.impots.gouv.fr/'),

        b_titre('Pimms Médiation Isère, France services', 2),
        b_paragraphe('Depuis 25 ans, les Pimms sont des lieux d’accueil ouverts à tous. Des médiateurs sociaux, professionnels du lien social, aident les habitants dans leurs démarches administratives pour un meilleur accès aux services publics.'),
        b_paragraphe('Ouvert à Villefontaine en 2014, le Pimms Médiation Isère œuvre à l’accessibilité des habitants aux services nécessaires à la vie courante : il relaie et facilite la compréhension, complète l’offre de services de ses partenaires et propose des services généralistes (information de premier niveau, mise en rapport avec le bon interlocuteur).'),
        b_contact('Pimms Médiation Isère de Villefontaine', adresse='8 place Jean Jaurès\n38090 Villefontaine', tel='04 37 05 03 69',
                  courriel='villefontaine@pimmsmediation.fr', lien='https://www.pimmsmediation.fr/'),
        b_paragraphe('Ses partenaires sont : la ville de Villefontaine, la CAPI, EDF, La Poste, SDH, ACTIS, CDC Habitat Social, Keolis Porte de l’Isère.'),
        b_paragraphe('Trois autres points d’accueil sont situés sur le territoire : à Grenoble, Vizille et à Pont-de-Claix pour le Sud-Isère, ainsi que deux bus France Services.'),
        b_paragraphe('L’équipe de Villefontaine est constituée de 5 médiateurs sociaux. Elle peut également accueillir des stagiaires (élèves, adultes en formation ou reconversion professionnelle).'),
        '\n\n'.join(accordeons),
        documents.group(0) if documents else '',
    ]
    p['contenu'] = '\n\n'.join(b for b in blocs if b)
    # Colonne latérale : contact général de la mairie (l'encart ne présentait que le Pimms).
    p['contact'] = {}
    return p


def toutes():
    return pages_budget()


def appliquer(pages):
    """Remplace dans la liste les pages recomposées (même clé) et ajoute les nouvelles, juste après leur page mère."""
    # Pages dont seule la structure change : le contenu converti est gardé, les blocs sont réordonnés.
    for q in pages:
        if q['cle'] == 'grands-rdv':
            recompose_grands_rdv(q)
        if q['cle'] == 'jumelage':
            recompose_jumelage(q)
        if q['cle'] == 'services-municipaux':
            recompose_services_municipaux(q)
        if q['cle'] == 'services-publics':
            recompose_services_publics(q)
    for p in toutes():
        positions = [i for i, q in enumerate(pages) if q['cle'] == p['cle']]
        if positions:
            pages[positions[0]] = p
            continue
        meres = [i for i, q in enumerate(pages) if q['cle'] == p['parent'] or q.get('parent') == p['parent']]
        pages.insert((meres[-1] + 1) if meres else len(pages), p)
    return pages


if __name__ == '__main__':
    lot = json.load(open('pages.json', encoding='utf-8'))
    avant = {p['cle'] for p in lot['pages']}
    appliquer(lot['pages'])
    json.dump(lot, open('pages.json', 'w', encoding='utf-8'), ensure_ascii=False)
    faites = [p['cle'] for p in toutes()]
    print(len(lot['pages']), 'pages ; recomposées :', [c for c in faites if c in avant] + ['grands-rdv', 'jumelage', 'services-municipaux', 'services-publics'], '; ajoutées :', [c for c in faites if c not in avant])
