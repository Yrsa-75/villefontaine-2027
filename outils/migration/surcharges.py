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
        img_bloc(U + '2024/07/Diagramme.png', 'Schéma explicatif du budget de la ville'),
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
