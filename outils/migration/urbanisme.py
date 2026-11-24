# -*- coding: utf-8 -*-
"""Page « Démarches d'urbanisme » : contenu fourni par le service urbanisme (novembre 2026),
avec le schéma interactif « quelles autorisations pour quels travaux ».

Module autonome (aucune dépendance) : il sert à la fois au lot de migration (surcharges.py)
et à la mise à jour directe de la page.
"""
import json

# Illustration du schéma : identifiant du média dans la médiathèque du site.
# 1829 = « schema-autorisations-travaux.webp » sur la préprod. À re-sélectionner dans le bloc si le site est reconstruit ailleurs.
IMAGE_SCHEMA = 1829

SP = 'https://www.service-public.gouv.fr/particuliers/vosdroits/'
ESPACE_CITOYEN = 'https://villefontaine.espace-citoyen.fr/#!portreq'
GNAU = 'https://gnau18.operis.fr/portedelisere/gnau/#/'

SIGLES = [
    {'sigle': 'DPC', 'libelle': 'Déclaration préalable de construction', 'url': SP + 'F17578', 'lienTexte': 'Démarche et formulaire'},
    {'sigle': 'DPA', 'libelle': 'Déclaration préalable d’aménagement', 'url': SP + 'R1995', 'lienTexte': 'Démarche et formulaire'},
    {'sigle': 'PC', 'libelle': 'Permis de construire', 'url': SP + 'R11637', 'lienTexte': 'Formulaire cerfa n° 13406'},
    {'sigle': 'PA', 'libelle': 'Permis d’aménager', 'url': SP + 'F17665', 'lienTexte': 'Démarche et formulaire'},
    {'sigle': 'PD', 'libelle': 'Permis de démolir', 'url': SP + 'R1980', 'lienTexte': 'Formulaire cerfa n° 13405'},
]

SURFACE_ANNEXE = '≤ 5 m² : aucune formalité\n≤ 20 m² : DPC\n> 20 m² : PC'

# (titre, règles, position horizontale en %, position verticale en %, texte du lien, adresse du lien)
# Les règles reprennent telles quelles celles du schéma fourni par le service urbanisme.
# Positions : garder au moins 8 % de largeur d'image entre deux points, pour qu'ils restent faciles à toucher sur téléphone.
POINTS = [
    ('Toiture', 'DPC', 11.5, 44.5, '', ''),
    ('Création d’une fenêtre de toit', 'Sans création de surface : DPC', 22.3, 46, '', ''),
    ('Pose de panneaux photovoltaïques', '# Hauteur au-dessus du sol < 1,80 m\n< 3 kW : aucune formalité\n# Hauteur au-dessus du sol > 1,80 m\n< 3 kW : DPC\n# Pour une puissance de\n≥ 3 kW et ≤ 250 kW : DPC\n≥ 250 kW : PC', 18.8, 37, '', ''),
    ('Ravalement de façade', 'DPC', 16.4, 56, '', ''),
    ('Porte, fenêtre ou porte-fenêtre', 'Création : DPC\nModification d’ouverture : DPC', 17.1, 66.5, '', ''),
    ('Extension, aménagement des combles', '≤ 40 m² : DPC\n> 40 m² : PC', 34.4, 56.5, '', ''),
    ('Création d’une véranda, terrasse couverte', '≤ 5 m² : aucune formalité\n≤ 40 m² : DPC\n> 40 m² : PC', 39.5, 42.5, '', ''),
    ('Terrasse non couverte', 'Sans surélévation : aucune formalité\nSurélevée ≤ 5 m² : aucune formalité\nSurélevée ≤ 20 m² : DPC\nSurélevée > 20 m² : PC', 49, 60, '', ''),
    ('Piscine', '# Non couverte, ou couverte < 1,80 m\nBassin < 10 m² : aucune formalité\nBassin < 100 m² : DPC\nBassin > 100 m² : PC\n# Couverte\n> 1,80 m : PC', 61.3, 54, '', ''),
    ('Abri de jardin, appentis', SURFACE_ANNEXE, 80.6, 40, '', ''),
    ('Serre', '≤ 5 m² : aucune formalité\n> 20 m² : DPC', 66.9, 39, '', ''),
    ('Garage ou carport', SURFACE_ANNEXE, 85, 53.5, '', ''),
    ('Portail et clôture, même en limite séparative', 'DPC', 20.6, 78.5, '', ''),
    ('Habitat léger', 'Plus de 3 mois : DPA', 60, 71, '', ''),
    ('Abattage d’arbre', 'DPA si espace boisé classé, ou arbre à préserver', 55.6, 27.5, '', ''),
    ('Démolition d’une construction', 'Inclus avec le PC ou le PA, sinon PD.', 81.3, 19, '', ''),
    ('Division de parcelle', 'DPA', 89.4, 35.5, 'Diviser un terrain : la démarche', SP + 'F37932'),
]


def attrs(o):
    """JSON d'attributs de bloc, échappé comme le fait l'éditeur de WordPress."""
    s = json.dumps(o, ensure_ascii=False, separators=(',', ':'))
    return s.replace('--', '\\u002d\\u002d').replace('<', '\\u003c').replace('>', '\\u003e').replace('&', '\\u0026')


def p(html, cls=''):
    a = (' ' + attrs({'className': cls})) if cls else ''
    c = (' class="%s"' % cls) if cls else ''
    return '<!-- wp:paragraph%s -->\n<p%s>%s</p>\n<!-- /wp:paragraph -->' % (a, c, html)


def titre(texte, niv=2):
    a = '' if niv == 2 else ' ' + attrs({'level': niv})
    return '<!-- wp:heading%s -->\n<h%d class="wp-block-heading">%s</h%d>\n<!-- /wp:heading -->' % (a, niv, texte, niv)


def bouton(texte, url, secondaire=False):
    a = (' ' + attrs({'className': 'is-style-secondaire'})) if secondaire else ''
    c = ' is-style-secondaire' if secondaire else ''
    return ('<!-- wp:buttons -->\n<div class="wp-block-buttons"><!-- wp:button%s -->\n<div class="wp-block-button%s"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>\n<!-- /wp:button --></div>\n<!-- /wp:buttons -->'
            % (a, c, url, texte))


def a_savoir(blocs):
    return '<!-- wp:group {"className":"is-style-a-savoir","layout":{"type":"default"}} -->\n<div class="wp-block-group is-style-a-savoir">%s</div>\n<!-- /wp:group -->' % '\n\n'.join(blocs)


def liste(items, style=''):
    a = (' ' + attrs({'className': style})) if style else ''
    cls = 'wp-block-list' + ((' ' + style) if style else '')
    lis = '\n\n'.join('<!-- wp:list-item -->\n<li>%s</li>\n<!-- /wp:list-item -->' % h for h in items)
    return '<!-- wp:list%s -->\n<ul class="%s">%s</ul>\n<!-- /wp:list -->' % (a, cls, lis)


def details(resume, blocs):
    return '<!-- wp:details -->\n<details class="wp-block-details"><summary>%s</summary>%s</details>\n<!-- /wp:details -->' % (resume, '\n\n'.join(blocs))


def schema():
    a = {
        'imageId': IMAGE_SCHEMA,
        'imageAlt': 'Maison individuelle et son jardin vus du dessus',
        'consigne': 'Cliquez sur un numéro pour savoir quel dossier déposer.',
        'rappel': '30 % (10 % dans certains cas) de la surface de la parcelle doit être capable d’absorber l’eau de pluie naturellement (pleine terre, pelouse ou revêtements drainants).',
        'titreListe': 'Tous les travaux en un coup d’œil',
        'niveau': 3,
        'sigles': SIGLES,
        'points': [{'titre': t, 'regles': r, 'x': x, 'y': y, 'lienTexte': lt, 'lienUrl': lu} for (t, r, x, y, lt, lu) in POINTS],
    }
    return '<!-- wp:vf/schema-interactif %s /-->' % attrs(a)


def demarches_urbanisme(ATTACH=None):
    """Blocs de la page, dans l'ordre."""
    sp = 'https://www.service-public.fr/particuliers/vosdroits/'
    return [
        titre('Quelles autorisations pour vos travaux ?'),
        p('<strong>Un projet de travaux ? Parlons-en avant de déposer votre dossier !</strong>', 'has-moyen-font-size').replace('{"className":"has-moyen-font-size"}', '{"fontSize":"moyen"}'),
        p('Construire, agrandir sa maison, créer ou modifier une fenêtre, installer une clôture ou des panneaux solaires, aménager son terrain… <strong>de nombreux projets nécessitent une demande auprès du service urbanisme de la commune.</strong>'),
        schema(),
        p('Les règles d’urbanisme peuvent parfois sembler complexes. C’est pourquoi <strong>il est conseillé de prendre contact avec le service urbanisme avant de déposer votre demande</strong>. Un rendez-vous permet de présenter votre projet, de poser vos questions et de connaître les démarches à effectuer.'),
        a_savoir([
            titre('Vous avez un doute ou souhaitez être accompagné ?', 3),
            p('N’hésitez pas à nous appeler ou à prendre rendez-vous en ligne sur votre espace citoyen. Le service urbanisme est là pour vous guider dans vos démarches.'),
            bouton('Prendre rendez-vous sur l’espace citoyen', ESPACE_CITOYEN),
        ]),
        titre('Vos démarches'),
        p('Une fois que vous avez repéré si vos travaux doivent faire l’objet d’un permis de construire (PC), d’une déclaration préalable de construction (DPC) ou d’aménagement (DPA), et une fois votre dossier constitué, vous pouvez le déposer auprès du service urbanisme en format numérique, sur le guichet numérique dédié. Pensez à créer un compte pour le dépôt et le suivi de votre dossier.'),
        bouton('Déposer votre dossier sur le guichet numérique', GNAU),
        titre('Vous préférez un dépôt papier ?', 3),
        p('Les formulaires d’autorisation d’urbanisme et leurs détails se trouvent sur <a href="https://www.service-public.gouv.fr/">service-public.gouv.fr</a>, en recherchant « urbanisme ».'),
        p('Le dépôt se fait en mairie, sur rendez-vous pris depuis <a href="%s">votre espace citoyen</a>.' % ESPACE_CITOYEN),
        titre('Vérifier la conformité de votre projet', 3),
        p('Pour vérifier la conformité de votre projet avec le Plan local d’urbanisme (PLU) de la ville, vous pouvez :'),
        liste([
            'retrouver les règles qui s’appliquent à votre terrain en saisissant votre adresse sur le <a href="https://www.geoportail-urbanisme.gouv.fr/">Géoportail de l’urbanisme</a> ;',
            'consulter <a href="/cadre-de-vie/urbanisme/plu/">le PLU de la commune</a>, sur ce site.',
        ], 'is-style-coches'),
        a_savoir([
            titre('Attention', 3),
            p('Si vous êtes en copropriété, votre projet doit également être accordé lors d’une assemblée générale.'),
        ]),
        titre('Une fois l’accord obtenu', 3),
        p('Dès l’obtention de l’accord des travaux, pensez à afficher sur votre portail un panneau de chantier. Une fois vos travaux terminés, déclarez-les en mairie avec le formulaire <a href="%sR1978">« Déclaration attestant l’achèvement et la conformité des travaux (DAACT) », cerfa n° 13408</a>.' % SP),
        titre('Comprendre les autorisations d’urbanisme'),
        details('Certificat d’urbanisme', [
            p('Le <strong>certificat d’urbanisme</strong> est un document qui indique les règles d’urbanisme applicables à un terrain donné. Il existe 2 types de certificats : <strong>le certificat d’information</strong> et <strong>le certificat opérationnel</strong>. Le certificat d’information permet de disposer d’informations sur la situation d’un terrain, tandis que le certificat opérationnel apporte des informations sur la faisabilité du projet. Sa délivrance n’est pas obligatoire, mais il est fortement recommandé d’en faire la demande.'),
        ]),
        details('Déclaration préalable', [
            p('Dans un certain nombre de cas, une <strong>déclaration préalable de travaux (DP)</strong> est exigée avant de démarrer les travaux. Elle est exigée pour des travaux qui ne sont pas soumis à un permis de construire. Elle permet à la mairie de vérifier que le projet de travaux respecte bien les règles d’urbanisme en vigueur.'),
            p('<a href="%sF17578">Plus d’informations sur la déclaration préalable</a>' % sp),
        ]),
        details('Permis de démolir', [
            p('<strong>Le permis de démolir</strong> est une autorisation administrative qui doit être obtenue préalablement à la démolition partielle ou totale de toute construction protégée ou située dans un secteur où ce permis est obligatoire. Il doit être adressé par lettre recommandée avec avis de réception ou déposé au service Urbanisme.'),
            p('Lorsque la démolition dépend d’un projet de construction ou d’aménagement, la demande de démolition peut être faite au moment de la demande de permis de construire ou d’aménager.'),
            p('La demande de permis est adressée à la mairie par l’une des personnes suivantes :'),
            liste([
                'le ou les propriétaires du terrain, leur mandataire, ou une ou plusieurs personnes attestant être autorisées par eux à exécuter les travaux ;',
                'le ou les co-indivisaires ou leur mandataire, en cas d’indivision ;',
                'la collectivité territoriale bénéficiant de l’expropriation pour cause d’utilité publique.',
            ]),
            p('Vous pouvez utiliser le service en ligne pour constituer votre dossier ou remplir un formulaire.'),
            p('<a href="%sF17669">Plus d’informations sur le permis de démolir</a>' % sp),
        ]),
        details('Permis de construire', [
            p('Le permis de construire est une autorisation d’urbanisme qui concerne les constructions nouvelles, même sans fondation, de plus de 20 m². Les travaux d’extension et le changement de destination des bâtiments existants peuvent également être soumis à permis. Les travaux qui ne relèvent pas du permis de construire sont en principe soumis à déclaration préalable de travaux.'),
            p('<a href="%sF1986">Plus d’informations sur le permis de construire</a>' % sp),
        ]),
        details('Permis d’aménager', [
            p('Le permis d’aménager est une autorisation d’urbanisme qui permet à la mairie de contrôler les aménagements réalisés sur un terrain (par exemple : la création d’un lotissement, d’aires de stationnement…).'),
            p('Les opérations soumises à cette autorisation peuvent varier si votre projet est situé dans le périmètre d’un secteur sauvegardé, dans un espace remarquable ou dans un milieu littoral à préserver.'),
            p('<a href="%sF17665">Plus d’informations sur le permis d’aménager</a>' % sp),
        ]),
    ]


def contenu_page():
    return '\n\n'.join(demarches_urbanisme())


if __name__ == '__main__':
    print(contenu_page())
