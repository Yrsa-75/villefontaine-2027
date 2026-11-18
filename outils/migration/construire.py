# -*- coding: utf-8 -*-
"""Construit le lot de migration (pages.json) à partir de l'export XML converti (items.json) et du plan."""
import json, re, sys, html
from urllib.parse import urlparse
sys.path.insert(0, '.')
from convertir import *
import plan
from surcharges import SURCHARGES

items = json.load(open('items.json'))
PAGES = {p['slug']: p for p in items if p['type'] == 'page' and p['statut'] == 'publish'}
ATTACH = {p['id']: p for p in items if p['type'] == 'attachment'}
PAR_ID = {p['id']: p for p in items if p['type'] == 'page'}

def chemin_ancien(p):
    return urlparse(p['lien']).path if p.get('lien') else '/' + p['slug'] + '/'

# ---------------------------------------------------------------------------
# 1. Arbre des nouvelles pages
# ---------------------------------------------------------------------------
noeuds = []          # liste ordonnée : dict(cle, titre, slug, parent, ordre, sources, opts, chemin, type)
def ajoute(cle, titre, slug, parent, ordre, sources, opts, genre):
    chemin = (next(n['chemin'] for n in noeuds if n['cle'] == parent) if parent else '/') + slug + '/'
    noeuds.append(dict(cle=cle, titre=titre, slug=slug, parent=parent, ordre=ordre, sources=sources, opts=opts, chemin=chemin, genre=genre))

for k, (rcle, rtitre, rdesc, enfants) in enumerate(plan.RUBRIQUES):
    ajoute(rcle, rtitre, rcle, None, k + 1, [], {'extrait': rdesc}, 'rubrique')
    for i, (cle, titre, slug, sources, opts) in enumerate(enfants):
        ajoute(cle, titre, slug, rcle, i + 1, sources, opts, 'rang2')
        for jj, (c3, t3, s3, src3, o3) in enumerate(opts.get("enfants", [])):
            ajoute(c3, t3, s3, cle, jj + 1, src3, o3, "rang3")
dcle, dtitre, ddesc, dpages = plan.DEMARCHES
ajoute(dcle, dtitre, 'demarches', None, 10, [], {'extrait': ddesc}, 'demarches-accueil')
for i, (cle, titre, slug, sources, extrait, icone) in enumerate(dpages):
    ajoute('demarches-' + cle, titre, slug, dcle, i + 1, sources, {'extrait': extrait, 'icone': icone}, 'demarches')
for i, (cle, titre, slug, sources, opts) in enumerate(plan.HORS_MENU):
    ajoute(cle, titre, slug, None, 20 + i, sources, opts, 'hors-menu')

NOUVEAU = {n['cle']: n for n in noeuds}

# Ancien chemin -> nouvelle adresse (avec ancre pour les démarches)
ancien_vers = {}
for n in noeuds:
    for s in n['sources']:
        p = PAGES[s]
        ancien_vers[chemin_ancien(p)] = (n['cle'], s if n['genre'] == 'demarches' else '')
for ancien, cible in plan.REDIRECTIONS_SUPPLEMENTAIRES.items():
    p = PAGES.get(ancien) or next((x for x in PAR_ID.values() if x['slug'] == ancien), None)
    ch = chemin_ancien(p) if p else '/' + ancien + '/'
    if ch not in ancien_vers:
        ancien_vers[ch] = (cible, '')

def nouvelle_adresse(url):
    """Lien interne vers une ancienne page -> nouvelle adresse relative (ou None si inconnu)."""
    u = urlparse(url)
    if u.netloc not in ('villefontaine.fr', 'www.villefontaine.fr') or u.path.startswith('/wp-content/'):
        return None
    ch = '/' + u.path.strip('/') + '/' if u.path.strip('/') else '/'
    if ch == '/':
        return '/'
    if ch in ancien_vers:
        cle, ancre = ancien_vers[ch]
        if cle.startswith('/'):
            return cle
        if cle == 'accueil':
            return '/'
        if cle in NOUVEAU:
            return NOUVEAU[cle]['chemin'] + (('#' + ancre) if ancre else '') + (('#' + u.fragment) if u.fragment and not ancre else '')
    if u.path.startswith('/events/'):
        return '/agenda/'
    return None

# ---------------------------------------------------------------------------
# 2. Nettoyages de texte
# ---------------------------------------------------------------------------
HUBS = {'/habitant/', '/professionnel/', '/association/', '/recherche-emploi/'}

def phrase(t):
    """TITRE EN CAPITALES -> Titre en capitales (on garde les sigles)."""
    lettres = [c for c in t if c.isalpha()]
    if lettres and sum(c.isupper() for c in lettres) / len(lettres) > 0.8 and len(lettres) > 4:
        mots = t.lower().split(' ')
        t = ' '.join(mots)
        t = t[:1].upper() + t[1:]
        for sigle in ('CCAS', 'PACS', 'CNI', 'PLU', 'SCOT', 'CAPI', 'SMND', 'PIMMS', 'RSA', 'CAF', 'MPT', 'CME', 'DASTRI', 'ENEDIS', 'SEMIDAO'):
            t = re.sub(r'\b' + sigle.lower() + r'\b', sigle, t)
    return t

def nettoie_blocs(blocs, cle):
    out = []
    for b in blocs:
        # Titres : capitales, « Cliquez… »
        m = re.match(r'(<!-- wp:heading[^>]*-->\n<h(\d) class="wp-block-heading">)(.*?)(</h\d>)', b, re.S)
        if m:
            texte = html.unescape(re.sub(r'<[^>]+>', '', m.group(3))).strip()
            if re.match(r'^(cliquez|cliquer)\b', texte, re.I) or not texte:
                continue
            b = b.replace(m.group(3), esc(phrase(texte)))
        # Documents : titres « cliquez ici »
        if b.startswith('<!-- wp:vf/documents'):
            d = json.loads(re.search(r'\{.*\}', b, re.S).group(0))
            for doc in d['documents']:
                t = re.sub(r'\s*[-:,(]?\s*(cliquez|cliquer)( ici| sur l[’\']image| sur le lien)?[\s.!:)]*', ' ', doc['titre'], flags=re.I).strip(' -:.')
                doc['titre'] = phrase(t) or 'Document'
            b = f'<!-- wp:vf/documents {j(d)} /-->'
        # Boutons vers les anciennes pages « carrefour » : supprimés
        if b.startswith('<!-- wp:buttons'):
            if any(urlparse(h).path in HUBS for h in re.findall(r'href="([^"]+)"', b)):
                continue
        out.append(b)
    return out

def reecrit_liens(texte):
    def rep(m):
        url = html.unescape(m.group(2))
        n = nouvelle_adresse(url)
        return m.group(1) + (html.escape(n) if n else m.group(2)) + m.group(3)
    texte = re.sub(r'(href=")([^"]+)(")', rep, texte)
    # Liens dans les attributs JSON des blocs (cartes, documents…)
    def rep_json(m):
        url = m.group(1).replace('\\/', '/')
        n = nouvelle_adresse(url)
        return '"url":"' + (n if n else m.group(1)) + '"'
    return re.sub(r'"url":"(https?:[^"]+)"', rep_json, texte)

def decale_titres(blocs, delta=1, mini=3, maxi=4):
    out = []
    for b in blocs:
        m = re.match(r'<!-- wp:heading(?: (\{.*?\}))? -->\n<h(\d) class="wp-block-heading">(.*?)</h\d>\n<!-- /wp:heading -->', b, re.S)
        if m:
            niv = min(maxi, max(mini, int(m.group(2)) + delta))
            b = b_titre(html.unescape(m.group(3)), niv)
        out.append(b)
    return out

# ---------------------------------------------------------------------------
# 3. Conversion d'une source
# ---------------------------------------------------------------------------
def convertit_source(slug):
    p = PAGES[slug]
    ctx = Contexte(p['titre'])
    d = p['meta'].get('_elementor_data')
    data = charge(d, None) if d else None
    if slug in SURCHARGES:
        blocs = SURCHARGES[slug](ATTACH)
    else:
        n = noeuds_elementor(data) if data else noeuds_html(p['contenu'])
        blocs = nettoie_blocs(convertir_noeuds(n, ctx), slug)
    image = ''
    tid = p['meta'].get('_thumbnail_id')
    if tid and tid.isdigit() and int(tid) in ATTACH:
        image = original(ATTACH[int(tid)]['url'])
    extrait = texte_propre(BeautifulSoup(p.get('extrait') or '', 'lxml').get_text())
    if extrait.endswith('(...)') or extrait.endswith('[…]'):
        extrait = ''
    return blocs, ctx, image, extrait

def premier_paragraphe_court(blocs):
    if blocs and blocs[0].startswith('<!-- wp:paragraph -->'):
        t = texte_propre(BeautifulSoup(blocs[0], 'lxml').get_text())
        if 40 < len(t) < 280 and '<a ' not in blocs[0]:
            return t, blocs[1:]
    return '', blocs

def premiere_image(blocs):
    if blocs and blocs[0].startswith('<!-- wp:image'):
        m = re.search(r'src="([^"]+)"', blocs[0])
        if m:
            return m.group(1), blocs[1:]
    return '', blocs

# ---------------------------------------------------------------------------
# 4. Composition des pages
# ---------------------------------------------------------------------------
def b_sous_pages(titre='Dans cette rubrique'):
    return f'<!-- wp:vf/sous-pages {j({"titre": titre, "lock": {"move": False, "remove": True}})} /-->'

def b_agenda(theme):
    a = {'format': 'liste', 'nombre': 3, 'titre': 'Prochains rendez-vous'}
    if isinstance(theme, str):
        a['thematique'] = theme
    else:
        a = {'format': 'cartes', 'nombre': 9}
    return f'<!-- wp:vf/agenda-prochains {j(a)} /-->'

ICONES = {'services-municipaux': 'maison', 'conseil-municipal': 'asso', 'budget': 'citoyen', 'budget-participatif': 'idee', 'jumelage': 'etoile',
          'marches-publics': 'doc', 'recrutement': 'famille', 'affichage-legal': 'info', 'enfance-famille': 'famille', 'jeunesse': 'ecole', 'seniors': 'coeur',
          'mobilite': 'bus', 'sante-solidarite': 'coeur', 'emploi': 'citoyen', 'commerces': 'tasse', 'services-publics': 'maison', 'tranquillite': 'alerte',
          'logement': 'maison', 'urbanisme': 'lieu', 'proprete': 'poubelle', 'accessibilite-ville': 'pmr', 'grands-projets': 'idee', 'patrimoine': 'arbre',
          'culture': 'etoile', 'sports': 'sport', 'jardins': 'arbre', 'grands-rdv': 'calendrier', 'associations': 'asso'}
pages_out, rapport = [], []
for n in noeuds:
    o = n['opts']
    blocs, contacts, image, extrait = [], [], '', o.get('extrait', '')
    notes = []
    sections = o.get('sections')
    for i, s in enumerate(n['sources']):
        bl, ctx, im, ex = convertit_source(s)
        notes += ctx.notes
        contacts += [c for c in ctx.contacts if any(c.values())]
        image = image or im
        if n['genre'] == 'demarches':
            intro_dem, bl2 = premier_paragraphe_court(bl)
            inner = decale_titres(bl, delta=1, mini=3, maxi=4)
            for c in ctx.contacts:
                if any(c.values()):
                    inner.append(b_contact(c))
            contacts = []
            titre = phrase(PAGES[s]['titre'])
            blocs.append(f'<!-- wp:details {j({"anchor": s})} -->\n<details id="{s}" class="wp-block-details"><summary>{esc(titre)}</summary>' + '\n\n'.join(inner or [b_paragraphe('Informations à venir.')]) + '</details>\n<!-- /wp:details -->')
            continue
        if not extrait and i == 0:
            extrait = ex
        if sections:
            blocs.append(b_titre(sections[i], 2))
            bl = decale_titres(bl, delta=1, mini=3, maxi=4)
        blocs.extend(bl)
    # Chapô : premier paragraphe court si pas d'extrait
    if not extrait and not sections and n['genre'] not in ('demarches',):
        extrait, blocs = premier_paragraphe_court(blocs)
    # Image mise en avant : image en tête de contenu
    if not image:
        image, blocs = premiere_image(blocs)
    # Contacts : le premier va dans l'encart de la colonne de droite, les autres dans le contenu.
    contact = {}
    if contacts:
        c0 = contacts[0]
        contact = {'titre': c0['titre'] or 'Contact', 'adresse': c0['adresse'], 'tel': c0['tel'], 'courriel': c0['courriel'], 'lien': c0['lien'], 'horaires': c0['texte']}
        if len(contacts) > 1:
            blocs.append(b_titre('Contacts', 2))
            blocs.extend(b_contact(c) for c in contacts[1:])
    # Blocs de structure selon le genre de page
    if n['genre'] == 'rubrique':
        blocs = [b_sous_pages('Dans cette rubrique')] + blocs
    elif n['genre'] == 'rang2' and o.get('enfants'):
        blocs = [b_sous_pages('Dans cette rubrique')] + blocs
    if o.get('cartes'):
        blocs.append(b_cartes([{'titre': c['titre'], 'texte': c['texte'], 'url': NOUVEAU[c['cle']]['chemin'], 'icone': c['icone']} for c in o['cartes']], 3))
    if o.get('agenda'):
        blocs.append(b_agenda(o['agenda']))
    if o.get('carte'):
        blocs.append('<!-- wp:vf/carte-services {"surtitre":"","titre":"Sur la carte","texte":"Retrouvez ces lieux sur la carte des services de la ville.","lienTexte":"Ouvrir la carte en grand","lienUrl":"/carte-des-services/"} /-->')
    if n['genre'] == 'demarches-accueil':
        cartes = [{'titre': t, 'texte': e, 'url': '/demarches/' + sl + '/', 'icone': ic} for (c, t, sl, src, e, ic) in dpages]
        blocs = [b_paragraphe('Retrouvez ici toutes les démarches administratives. Beaucoup se font en ligne, sur l’<a href="https://villefontaine.espace-citoyen.fr/#!portreq">Espace citoyen</a> ou sur <a href="https://www.service-public.fr/">service-public.fr</a>.'),
                 b_cartes(cartes, 3),
                 b_boutons([('Accéder à l’Espace citoyen', 'https://villefontaine.espace-citoyen.fr/#!portreq'), ('Portail Famille', 'https://villefontaine.portailfamille.net/')])]
    if n['genre'] == 'demarches':
        blocs = [b_paragraphe('Cliquez sur une démarche pour afficher les informations : à qui elle s’adresse, les pièces à fournir et comment la faire.')] + blocs
    contenu = reecrit_liens('\n\n'.join(b for b in blocs if b))
    if not contenu.strip():
        notes.append('page sans contenu')
    pleine = o.get('pleine') or n['genre'] in ('demarches', 'demarches-accueil', 'rubrique')
    pages_out.append({
        'cle': n['cle'], 'titre': n['titre'], 'slug': n['slug'], 'parent': n['parent'], 'ordre': n['ordre'],
        'extrait': extrait[:300], 'contenu': contenu, 'image': image, 'gabarit': 'page-pleine-largeur' if pleine else '',
        'contact': contact, 'statut': 'publish', 'icone': ICONES.get(n['cle'], o.get('icone', '')),
    })
    rapport.append(f"{n['chemin']:52} {n['genre']:17} {len(contenu):6} car. contacts:{len(contacts)} image:{'oui' if image else 'non'} {'; '.join(sorted(set(notes)))}")

# ---------------------------------------------------------------------------
# 5. Menu principal et redirections
# ---------------------------------------------------------------------------
menu = []
for rcle, rtitre, rdesc, enfants in plan.RUBRIQUES:
    menu.append({'titre': rtitre, 'cle': rcle, 'description': rdesc, 'enfants': [
        {'titre': t, 'cle': c, 'enfants': [{'titre': t3, 'cle': c3} for (c3, t3, s3, src3, o3) in o.get('enfants', [])]}
        for (c, t, s, src, o) in enfants]})

redirections = {}
for ancien, (cle, ancre) in ancien_vers.items():
    if ancien in ('/',):
        continue
    redirections[ancien] = (NOUVEAU[cle]['chemin'] + '#' + ancre) if ancre and cle in NOUVEAU else cle

json.dump({'version': 1, 'pages': pages_out, 'menu': menu, 'redirections': redirections}, open('pages.json', 'w'), ensure_ascii=False)
print('\n'.join(rapport))
print(len(pages_out), 'pages,', len(redirections), 'redirections')

# ---------------------------------------------------------------------------
# 6. Actualités et événements publiés depuis le 1er janvier 2026
# ---------------------------------------------------------------------------
import datetime
DEPUIS = datetime.datetime(2026, 1, 1, tzinfo=datetime.timezone.utc)

def convertit_item(p):
    ctx = Contexte(p['titre'])
    d = p['meta'].get('_elementor_data')
    data = charge(d, None) if d else None
    n = noeuds_elementor(data) if data else noeuds_html(p['contenu'])
    blocs = nettoie_blocs(convertir_noeuds(n, ctx), p['slug'])
    for c in ctx.contacts:
        if any(c.values()):
            blocs.append(b_contact(c))
    image = ''
    tid = p['meta'].get('_thumbnail_id')
    if tid and tid.isdigit() and int(tid) in ATTACH:
        image = original(ATTACH[int(tid)]['url'])
    if not image:
        image, blocs = premiere_image(blocs)
    # Une image identique à l'image mise en avant en tête de contenu ferait doublon.
    if image and blocs and blocs[0].startswith('<!-- wp:image') and image in blocs[0]:
        blocs = blocs[1:]
    return reecrit_liens('\n\n'.join(b for b in blocs if b)), image

actus = []
for p in items:
    if p['type'] == 'post' and p['statut'] == 'publish' and p['date'] >= '2026-01-01':
        contenu, image = convertit_item(p)
        ext = texte_propre(BeautifulSoup(p.get('extrait') or '', 'lxml').get_text())
        actus.append({'cle': 'actu-' + str(p['id']), 'titre': phrase(html.unescape(p['titre'])), 'slug': p['slug'], 'date': p['date'],
                      'extrait': ext[:300], 'contenu': contenu, 'image': image,
                      'categories': [t[2] for t in p['termes'] if t[0] == 'category'],
                      'ancien': chemin_ancien(p)})

evts = []
for p in items:
    if p['type'] != 'ajde_events' or p['statut'] != 'publish':
        continue
    m = p['meta']
    d0 = datetime.datetime.fromtimestamp(int(m.get('evcal_srow') or 0), datetime.timezone.utc)
    if d0 < DEPUIS:
        continue
    d1 = datetime.datetime.fromtimestamp(int(m.get('evcal_erow') or m.get('evcal_srow')), datetime.timezone.utc)
    journee = m.get('evcal_allday') == 'yes'
    contenu, image = convertit_item(p)
    titre = html.unescape(p['titre']).strip()
    evts.append({'cle': 'evt-' + str(p['id']), 'titre': titre, 'slug': p['slug'], 'date': p['date'], 'contenu': contenu, 'image': image,
                 'debut': d0.strftime('%Y-%m-%d'), 'fin': d1.strftime('%Y-%m-%d') if d1.date() > d0.date() else '',
                 'heure_debut': '' if journee else d0.strftime('%H:%M'),
                 'heure_fin': '' if journee or m.get('evo_hide_endtime') == 'yes' or d1 <= d0 else d1.strftime('%H:%M'),
                 'lieu': next((t[2] for t in p['termes'] if t[0] == 'event_location'), ''),
                 'themes': [t[2] for t in p['termes'] if t[0] == 'event_type'],
                 'infos': html.unescape(m.get('evcal_subtitle') or '').strip(),
                 'lien': m.get('evcal_lmlink') or '',
                 'annule': bool(re.search(r'annul', titre, re.I)),
                 'ancien': chemin_ancien(p)})
json.dump({'version': 1, 'actualites': actus, 'evenements': evts}, open('contenus.json', 'w'), ensure_ascii=False)
print(len(actus), 'actualités,', len(evts), 'événements')
