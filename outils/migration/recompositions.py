# -*- coding: utf-8 -*-
"""
Pages recomposées à la main pour suivre le document « ARCHITECTURE SITE NEW » (25/11/2026) : les passages en bleu
du document décrivent ce que chaque page doit contenir.

Retour de la mairie du 27/11/2026 : « la même simplicité qu'epernay.fr : un pavé, une image, un titre, vers une page
de contenu ». Budget, Jumelage et Services publics ne contiennent donc plus que des pavés cliquables vers leurs
sous-pages ; Services municipaux n'est plus qu'un annuaire de cartes.

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


def b_sous_pages(titre, masque=False):
    """Pavés automatiques vers les sous-pages. masque=True : la page ne contient que les pavés, le titre de section
    n'est pas affiché (il reste lu par les lecteurs d'écran)."""
    a = {"titre": titre, "lock": {"move": False, "remove": True}}
    if masque:
        a["titreMasque"] = True
    return f'<!-- wp:vf/sous-pages {j(a)} /-->'


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
            # Retour de la mairie du 27/11/2026 : uniquement les pavés cliquables (ni introduction, ni lexique).
            b_sous_pages('Consulter le budget', masque=True),
        ],
        icone='citoyen', image=DOCS + '2021/04/budget-participatif-freepik-scaled.jpg', gabarit='page-pleine-largeur',
    )]
    for rang, (annee, docs) in enumerate(BUDGETS, start=1):
        pages.append(page(
            f'budget-{annee}', f'Budget {annee}', f'budget-{annee}', 'budget', rang, resume_annee(annee, docs),
            [
                b_paragraphe(f'Les documents du budget {annee} de la commune, tels qu’ils ont été présentés au conseil municipal.'),
                b_documents([(t, DOCS + u) for t, u in docs]),
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
# 3. Jumelage : 4 pavés cliquables, une page par ville jumelle (retour de la mairie du 27/11/2026)
# ---------------------------------------------------------------------------
# Textes repris de la page du site actuel (« Zoom sur nos villes jumelées »), fautes de noms propres corrigées
# (Francfort, la Hesse, Saxe-Anhalt, Kahl am Main). L'historique et « Qu'est-ce que le jumelage ? » ne sont pas repris
# (la mairie les juge en doublon). Le 26/11, Ju avait choisi des rectangles sans sous-page ; la mairie a demandé
# le 27/11 un accès direct vers une page par ville.
VILLES_JUMELLES = [
    ('kahl-am-main', 'Kahl am Main', 'Allemagne, jumelée depuis 1981.',
     ['Premier partenaire de Villefontaine. Kahl am Main est situé à 30 kilomètres de Francfort, à la limite entre la Hesse et la Bavière, dans une région d’étangs et de forêts.']),
    ('bitterfeld-wolfen', 'Bitterfeld-Wolfen', 'Allemagne, jumelée depuis le 23 avril 1994.',
     ['Notre second jumelage allemand s’est fait afin de développer les échanges scolaires pour les élèves du secondaire, ce qui n’est pas possible à Kahl am Main, dépourvu de lycées.',
      'Située à 30 km de Leipzig, dans le Land de Saxe-Anhalt, Wolfen s’honore d’un passé qui remonte à 600 ans.']),
    ('gremda', 'Gremda', 'Tunisie, jumelée depuis le 6 novembre 1994.',
     ['La ville est située à une dizaine de kilomètres au nord-ouest de Sfax, dans la région qu’on appelle le Sahel. Depuis la signature du jumelage avec Villefontaine, plusieurs échanges scolaires ont déjà eu lieu.']),
    ('salzano', 'Salzano', 'Italie, jumelée depuis le 16 mai 2009.',
     ['Ce jumelage a été conclu dans une volonté de consolider la connaissance et l’amitié entre les deux communautés. Salzano fait partie de la ville métropolitaine de Venise, en Vénétie.']),
]


def recompose_jumelage(p):
    """La page ne contient plus que les 4 pavés (bloc automatique : une page ajoutée sous Jumelage crée son pavé)."""
    p['contenu'] = b_sous_pages('Nos villes jumelées', masque=True)
    p['gabarit'] = 'page-pleine-largeur'   # uniquement les pavés : pas de colonne latérale
    return p


def pages_jumelage():
    pages = []
    for rang, (slug, ville, quand, texte) in enumerate(VILLES_JUMELLES, start=1):
        pages.append(page(f'jumelage-{slug}', ville, slug, 'jumelage', rang, quand,
                          [b_paragraphe(x) for x in texte], icone='lieu'))
    return pages


# ---------------------------------------------------------------------------
# 4. Les services municipaux : uniquement l'annuaire des services, en cartes (retour de la mairie du 27/11/2026)
# ---------------------------------------------------------------------------
# Coordonnées et horaires repris des 11 accordéons de la page du site actuel (un accordéon regroupait parfois
# plusieurs services : ils ont chacun leur carte). Retirés de la page : la partie « Contacter la mairie » (ses
# informations sont dans la première carte), le visuel de l'hôtel de ville rénové, la carte des services et la phrase
# d'attente de l'organigramme.
COURRIEL_MAIRIE = 'contact.habitants@mairie-villefontaine.fr'
RDV = 'Ouvert au public sur rendez-vous.'
SERVICES_MUNICIPAUX = [
    dict(titre='Accueil de l’hôtel de ville', texte='Les services accueillent le public uniquement sur rendez-vous.',
         adresse='Place Pierre Mendès France\n38090 Villefontaine', tel='04 74 96 00 00', courriel=COURRIEL_MAIRIE,
         horaires='Lundi, mardi, mercredi et vendredi : 8 h 30 à 12 h et 13 h 30 à 17 h\nJeudi : 10 h 30 à 12 h et 13 h 30 à 17 h\n1er samedi du mois : 8 h 30 à 12 h'),
    dict(titre='État civil', texte=RDV + ' Carte d’identité ou passeport : attendez le SMS qui signale son arrivée en mairie avant de prendre rendez-vous pour le retrait.',
         lien='https://villefontaine.espace-citoyen.fr/#!portreq', lienTexte='Prendre rendez-vous sur l’Espace citoyen'),
    dict(titre='Éducation', texte=RDV, tel='04 74 96 00 00'),
    dict(titre='Urbanisme', texte=RDV, tel='04 74 96 70 20'),
    # Sur le site actuel, l'adresse affichée (info-jeunes@) et l'adresse du lien (bureau.information.jeunesse@) diffèrent :
    # c'est l'adresse affichée qui est reprise, à faire confirmer par la mairie.
    dict(titre='Info jeunes Villefontaine', courriel='info-jeunes@mairie-villefontaine.fr',
         horaires='Lundi : 13 h 30 à 17 h 30\nMercredi : 9 h à 12 h et 13 h 30 à 17 h 30\nVendredi : 13 h 30 à 17 h 30'),
    dict(titre='Centre communal d’action sociale (CCAS)', tel='04 74 96 70 35',
         horaires='Du mardi au vendredi : 9 h à 12 h 30 et 13 h 30 à 17 h\nFermé au public le lundi'),
    dict(titre='Centre technique municipal (CTM)', adresse='9 rue Benoît Frachon', tel='04 74 96 02 18',
         courriel='accueil.ctm@mairie-villefontaine.fr', horaires='Du lundi au vendredi : 8 h 30 à 12 h'),
    dict(titre='Coordinatrice sociale en gendarmerie', texte='Pour toute problématique sociale en lien avec la gendarmerie.', tel='06 16 14 16 06',
         horaires='Lundi, mardi, jeudi et vendredi : 9 h à 12 h et 14 h à 17 h 30\nMercredi : 9 h à 12 h'),
    dict(titre='Vie sportive et culturelle', courriel='associationsportculture@mairie-villefontaine.fr'),
    dict(titre='Économie', texte='Commerces, marchés, entreprises.', courriel='deveco@mairie-villefontaine.fr'),
    dict(titre='Le Phare', texte='Au service des Villards de plus de 60 ans.', adresse='15 F impasse Ambroise Croizat', tel='04 74 96 58 61'),
    dict(titre='Maison de la justice et du droit (MJD)', tel='04 74 96 94 67', courriel='mjd@mairie-villefontaine.fr'),
    dict(titre='Maison pour tous Denise Tronchet', texte='Servenoble et Saint-Bonnet.', courriel='mpt.tronchet@mairie-villefontaine.fr'),
    dict(titre='Maison pour tous Abdellatif Loukili', texte='Les Roches.', courriel='mpt.loukili@mairie-villefontaine.fr'),
    dict(titre='Maison pour tous Florence Arthaud', texte='Les Fougères.', courriel='mpt.arthaud@mairie-villefontaine.fr'),
    dict(titre='Police municipale', texte='Urgences : composez le 17.', adresse='15 rue Serge Mauroit', tel='04 74 96 66 15',
         horaires='Accueil téléphonique et physique\nDu lundi au vendredi : 9 h à 12 h et 13 h à 16 h'),
]


def b_contact(titre, **champs):
    d = {'titre': titre, 'texte': '', 'adresse': '', 'tel': '', 'courriel': '', 'lien': '', 'horaires': ''}
    d.update(champs)
    return f'<!-- wp:vf/contact-carte {j(d)} /-->'


def b_grille_contacts(cartes, niveau=2):
    """Annuaire : cartes contact rangées en grille. Le nom de chaque service est un titre (niveau 2 par défaut)."""
    dedans = '\n\n'.join(b_contact(c['titre'], niveau=niveau, **{k: v for k, v in c.items() if k != 'titre'}) for c in cartes)
    return ('<!-- wp:group {"className":"vf-grille-contacts","layout":{"type":"default"}} -->\n'
            '<div class="wp-block-group vf-grille-contacts">' + dedans + '</div>\n<!-- /wp:group -->')


def recompose_services_municipaux(p):
    p['contenu'] = b_grille_contacts(SERVICES_MUNICIPAUX)
    p['gabarit'] = 'page-pleine-largeur'   # pas de colonne latérale : les cartes prennent toute la largeur
    p['contact'] = {}
    return p


# ---------------------------------------------------------------------------
# 5. Services publics : 4 pavés cliquables, une page par organisme (retour de la mairie du 27/11/2026)
# ---------------------------------------------------------------------------
# D'où viennent les contenus (question de la mairie du 27/11/2026) :
# - Pimms : textes, accordéons, documents et coordonnées repris de la page « Services publics » du site actuel ;
# - France Travail, La Poste, impôts : ces organismes n'ont aucun contenu sur le site actuel. Les textes de présentation
#   ont été rédigés par YRSA et les coordonnées relevées le 26/11/2026 sur : francetravail.fr (page de l'agence de
#   Villefontaine), le localisateur de bureaux de La Poste (bureau des Dauphins), un annuaire NON officiel pour l'adresse
#   du service des impôts, et le numéro national des impôts des particuliers. Tout est à valider par la mairie.
def pages_services_publics(source):
    """source : contenu d'où reprendre les accordéons et les documents du Pimms (ancienne page ou sous-page Pimms)."""
    import re
    accordeons = re.findall(r'<!-- wp:details -->.*?<!-- /wp:details -->', source, re.S)
    documents = re.search(r'<!-- wp:vf/documents [^>]*?/-->', source)
    # Le Pimms mobile : la liste des dates d'avril est périmée, la tournée à jour est dans le document joint.
    accordeons = [re.sub(r'\s*<!-- wp:list -->\s*<ul class="wp-block-list"><!-- wp:list-item -->\s*<li>Mardi 1er avril</li>.*?<!-- /wp:list -->', '', a, flags=re.S) for a in accordeons]
    mere = 'services-publics'
    return [
        page('services-publics-france-travail', 'France Travail', 'france-travail', mere, 1,
             'Demande d’emploi, allocations, accompagnement : l’agence France Travail de Villefontaine.', [
                 b_paragraphe('France Travail s’occupe principalement des dossiers de demande d’emploi et du versement des allocations de chômage. L’agence propose un accompagnement personnalisé vers l’emploi et des ateliers collectifs thématiques (CV, lettre de motivation, etc.).'),
                 b_contact('Agence France Travail de Villefontaine', texte='Employeurs : composez le 3995.',
                           adresse='315 rue Serge Mauroit\n38090 Villefontaine', tel='3949',
                           horaires='Sans rendez-vous : du lundi au vendredi, de 8 h 30 à 12 h 30\nSur rendez-vous : lundi, mardi et mercredi de 12 h 30 à 16 h 30, vendredi de 12 h 30 à 15 h 30',
                           lien='https://www.francetravail.fr/annuaire/votre-agence-francetravail/villefontaine-38090'),
             ], icone='citoyen'),
        page('services-publics-la-poste', 'La Poste', 'la-poste', mere, 2,
             'Le bureau de poste et l’agence postale communale de Villefontaine.', [
                 b_paragraphe('Deux points de contact La Poste vous accueillent à Villefontaine.'),
                 b_contact('Bureau de poste Villefontaine Les Dauphins', adresse='Place de l’Hôtel de Ville\n38090 Villefontaine',
                           horaires='Du lundi au vendredi : 9 h à 12 h 30 et 14 h à 17 h 30\nSamedi : 9 h à 12 h',
                           lien='https://www.labanquepostale.fr/particulier/localisation_bureau/resultat_localisation_bureau.detail.html/villefontaine-lesdauphins-385530.html'),
                 b_contact('Agence postale communale', adresse='601 chaussée des Escoffiers\n38090 Villefontaine', lien='https://www.laposte.fr/bureaux-poste'),
             ], icone='courriel'),
        page('services-publics-impots', 'Impôts', 'impots', mere, 3,
             'Le service des impôts des particuliers et les démarches en ligne.', [
                 b_paragraphe('Le service des impôts des particuliers est installé au centre des finances publiques, place Charles-de-Gaulle. La plupart des démarches peuvent aussi se faire en ligne, depuis votre espace particulier sur impots.gouv.fr.'),
                 b_contact('Service des impôts des particuliers', adresse='Centre des finances publiques\nPlace Charles-de-Gaulle\n38097 Villefontaine Cedex',
                           tel='0 809 401 401', lien='https://www.impots.gouv.fr/'),
             ], icone='doc'),
        page('services-publics-pimms', 'Pimms Médiation Isère, France services', 'pimms-france-services', mere, 4,
             'Un lieu d’accueil ouvert à tous pour être aidé dans ses démarches administratives.', [
                 b_paragraphe('Depuis 25 ans, les Pimms sont des lieux d’accueil ouverts à tous. Des médiateurs sociaux, professionnels du lien social, aident les habitants dans leurs démarches administratives pour un meilleur accès aux services publics.'),
                 b_paragraphe('Ouvert à Villefontaine en 2014, le Pimms Médiation Isère œuvre à l’accessibilité des habitants aux services nécessaires à la vie courante : il relaie et facilite la compréhension, complète l’offre de services de ses partenaires et propose des services généralistes (information de premier niveau, mise en rapport avec le bon interlocuteur).'),
                 b_contact('Pimms Médiation Isère de Villefontaine', adresse='8 place Jean Jaurès\n38090 Villefontaine', tel='04 37 05 03 69',
                           courriel='villefontaine@pimmsmediation.fr', lien='https://www.pimmsmediation.fr/'),
                 b_paragraphe('Ses partenaires sont : la ville de Villefontaine, la CAPI, EDF, La Poste, SDH, ACTIS, CDC Habitat Social, Keolis Porte de l’Isère.'),
                 b_paragraphe('Trois autres points d’accueil sont situés sur le territoire : à Grenoble, Vizille et à Pont-de-Claix pour le Sud-Isère, ainsi que deux bus France Services.'),
                 b_paragraphe('L’équipe de Villefontaine est constituée de 5 médiateurs sociaux. Elle peut également accueillir des stagiaires (élèves, adultes en formation ou reconversion professionnelle).'),
                 '\n\n'.join(accordeons),
                 documents.group(0) if documents else '',
             ], icone='info'),
    ]


def recompose_services_publics(p):
    """La page ne contient plus que les 4 pavés. La photo du Pimms du bandeau est retirée (elle « mélangeait tout »)."""
    p['contenu'] = b_sous_pages('Les services publics à Villefontaine', masque=True)
    p['image'] = ''
    p['contact'] = {}
    p['gabarit'] = 'page-pleine-largeur'
    return p


def toutes(source_pimms=''):
    return pages_budget() + pages_jumelage() + pages_services_publics(source_pimms)


def appliquer(pages):
    """Remplace dans la liste les pages recomposées (même clé) et ajoute les nouvelles, juste après leur page mère."""
    par_cle = {q['cle']: q for q in pages}
    # Accordéons et documents du Pimms : pris dans l'ancienne page Services publics, ou dans la sous-page si elle existe déjà.
    source_pimms = next((par_cle[c]['contenu'] for c in ('services-publics', 'services-publics-pimms')
                         if c in par_cle and 'wp:details' in par_cle[c]['contenu']), '')
    for q in pages:
        if q['cle'] == 'grands-rdv':
            recompose_grands_rdv(q)
        if q['cle'] == 'jumelage':
            recompose_jumelage(q)
        if q['cle'] == 'services-municipaux':
            recompose_services_municipaux(q)
        if q['cle'] == 'services-publics':
            recompose_services_publics(q)
    for p in toutes(source_pimms):
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
    faites = [p['cle'] for p in toutes()]  # (sans source : seules les clés servent ici)
    print(len(lot['pages']), 'pages ; recomposées :', [c for c in faites if c in avant] + ['grands-rdv', 'jumelage', 'services-municipaux', 'services-publics'], '; ajoutées :', [c for c in faites if c not in avant])
