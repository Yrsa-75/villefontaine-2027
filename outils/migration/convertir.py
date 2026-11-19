# -*- coding: utf-8 -*-
"""
Conversion des pages Elementor du site actuel en « nœuds » sémantiques,
puis en blocs WordPress propres (blocs natifs + blocs Villefontaine).
"""
import json, re, html, hashlib
from bs4 import BeautifulSoup, NavigableString, Tag

UPLOADS = 'https://villefontaine.fr/wp-content/uploads/'
EXT_DOCS = ('.pdf', '.doc', '.docx', '.xls', '.xlsx', '.odt', '.ods', '.ppt', '.pptx', '.zip')

def j(o):
    return json.dumps(o, ensure_ascii=False, separators=(',', ':'))

def esc(t):
    return html.escape(t or '', quote=False)

def original(url):
    """Adresse de l'image d'origine (sans le suffixe -300x200 des miniatures)."""
    return re.sub(r'-\d{2,4}x\d{2,4}(?=\.(?:jpe?g|png|gif|webp)$)', '', url or '', flags=re.I)

def est_doc(url):
    return (url or '').split('?')[0].lower().endswith(EXT_DOCS)

# ---------------------------------------------------------------------------
# 1. Elementor -> nœuds
# ---------------------------------------------------------------------------
def charge(s, defaut=None):
    if isinstance(s, (dict, list)):
        return s
    try:
        return json.loads(s)
    except Exception:
        return defaut

def noeuds_elementor(data):
    out = []
    def lien(s):
        l = charge(s.get('link'), {}) or {}
        return l.get('url', '') if isinstance(l, dict) else ''
    def walk(n):
        if isinstance(n, list):
            for x in n:
                walk(x)
            return
        if not isinstance(n, dict):
            return
        if n.get('elType') == 'widget':
            w = n.get('widgetType'); s = n.get('settings', {})
            if w == 'heading':
                out.append({'t': 'titre', 'niv': s.get('header_size', 'h2'), 'texte': BeautifulSoup(s.get('title', ''), 'lxml').get_text(' ', strip=True), 'url': lien(s)})
            elif w == 'text-editor':
                out.append({'t': 'html', 'html': s.get('editor', '')})
            elif w == 'button':
                out.append({'t': 'bouton', 'texte': BeautifulSoup(s.get('text', ''), 'lxml').get_text(' ', strip=True), 'url': lien(s)})
            elif w in ('toggle', 'accordion'):
                tabs = charge(s.get('tabs'), []) or []
                out.append({'t': 'plis', 'items': [(t.get('tab_title', ''), t.get('tab_content', '')) for t in tabs]})
            elif w == 'pp-advanced-accordion':
                tabs = charge(s.get('tabs'), []) or []
                out.append({'t': 'plis', 'items': [(t.get('tab_title', ''), t.get('accordion_content', '')) for t in tabs]})
            elif w == 'counter':
                out.append({'t': 'chiffre', 'nombre': str(s.get('ending_number', '')), 'avant': s.get('prefix', ''), 'apres': s.get('suffix', ''), 'libelle': s.get('title', '')})
            elif w == 'alert':
                out.append({'t': 'alerte', 'titre': s.get('alert_title', ''), 'texte': s.get('alert_description', '')})
            elif w == 'wp-widget-ocean_contact_info':
                out.append({'t': 'contact', 'd': charge(s.get('wp'), {}) or {}})
            elif w == 'image':
                im = charge(s.get('image'), {}) or {}
                if im.get('url'):
                    out.append({'t': 'image', 'url': original(im['url']), 'alt': im.get('alt', ''), 'url_lien': lien(s), 'legende': s.get('caption', '')})
            elif w in ('image-gallery', 'gallery', 'image-carousel', 'media-carousel'):
                g = charge(s.get('wp_gallery') or s.get('gallery') or s.get('carousel') or s.get('slides'), []) or []
                urls = [original(x.get('url', '')) for x in g if isinstance(x, dict) and x.get('url')]
                if urls:
                    out.append({'t': 'galerie', 'urls': urls})
            elif w == 'image-box':
                im = charge(s.get('image'), {}) or {}
                out.append({'t': 'carte', 'titre': BeautifulSoup(s.get('title_text', ''), 'lxml').get_text(' ', strip=True), 'texte': BeautifulSoup(s.get('description_text', ''), 'lxml').get_text(' ', strip=True), 'image': original(im.get('url', '')), 'url': lien(s)})
            elif w == 'call-to-action':
                im = charge(s.get('bg_image'), {}) or {}
                out.append({'t': 'carte', 'titre': s.get('title', ''), 'texte': BeautifulSoup(s.get('description', ''), 'lxml').get_text(' ', strip=True), 'image': original(im.get('url', '')), 'url': lien(s), 'bouton': s.get('button', '')})
            elif w == 'icon-list':
                items = charge(s.get('icon_list'), []) or []
                out.append({'t': 'liste', 'items': [(BeautifulSoup(i.get('text', ''), 'lxml').get_text(' ', strip=True), (charge(i.get('link'), {}) or {}).get('url', '') if isinstance(charge(i.get('link'), {}), dict) else '') for i in items]})
            elif w == 'video':
                url = s.get('youtube_url') if s.get('video_type', 'youtube') == 'youtube' else s.get('vimeo_url')
                out.append({'t': 'video', 'url': url or s.get('youtube_url', '')})
            elif w == 'blockquote':
                out.append({'t': 'citation', 'texte': s.get('blockquote_content', ''), 'auteur': s.get('author_name', '')})
            elif w == 'html':
                out.append({'t': 'html_brut', 'html': s.get('html', '')})
            elif w == 'google_maps':
                out.append({'t': 'carte_google', 'adresse': s.get('address', '')})
            elif w == 'form':
                out.append({'t': 'formulaire'})
            elif w == 'price-list':
                items = charge(s.get('price_list'), []) or []
                out.append({'t': 'tarifs', 'items': [(i.get('title', ''), i.get('price', ''), i.get('item_description', '')) for i in items]})
            # Ignorés : spacer, divider, theme-post-title, posts, social-icons, breadcrumbs, shortcode, nav-menu…
        walk(n.get('elements', []))
    walk(data)
    return out

def noeuds_html(h):
    """Pages sans Elementor : le contenu HTML devient un seul nœud."""
    return [{'t': 'html', 'html': h}]

# ---------------------------------------------------------------------------
# 2. Nettoyage HTML -> blocs natifs
# ---------------------------------------------------------------------------
GARDER = {'p', 'ul', 'ol', 'li', 'a', 'strong', 'em', 'b', 'i', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'blockquote', 'img', 'sup', 'sub'}

def nettoie(h):
    s = BeautifulSoup(h or '', 'lxml')
    for t in s(['script', 'style', 'iframe', 'form', 'noscript', 'svg', 'button', 'input', 'select', 'textarea']):
        t.decompose()
    for t in s.find_all(True):
        if t.name not in GARDER:
            t.unwrap(); continue
        attrs = {}
        if t.name == 'a' and t.get('href'):
            attrs['href'] = t['href'].strip()
        if t.name == 'img' and t.get('src'):
            attrs['src'] = original(t['src']); attrs['alt'] = t.get('alt', '')
        if t.name in ('td', 'th'):
            for k in ('colspan', 'rowspan'):
                if t.get(k): attrs[k] = t[k]
        t.attrs = attrs
    for t in s.find_all(['b']):
        t.name = 'strong'
    for t in s.find_all(['i']):
        t.name = 'em'
    corps = s.body or s
    return corps

def texte_propre(x):
    return re.sub(r'\s+', ' ', x.replace('\xa0', ' ')).strip()

def inline_html(tag):
    """HTML en ligne d'un élément (liens, gras…), espaces normalisés."""
    h = ''.join(str(c) for c in tag.contents)
    h = h.replace('\xa0', ' ').replace('&nbsp;', ' ')
    h = re.sub(r'\s+', ' ', h).strip()
    h = re.sub(r'(<br/?>\s*){2,}', '<br>', h)
    h = re.sub(r'^(<br/?>\s*)+|(\s*<br/?>)+$', '', h)
    h = re.sub(r'<(strong|em)>\s*</\1>', '', h)
    return h

def b_paragraphe(h, cls=''):
    if not texte_propre(BeautifulSoup(h, 'lxml').get_text()):
        return ''
    a = {'className': cls} if cls else {}
    return f'<!-- wp:paragraph{(" " + j(a)) if a else ""} -->\n<p{(" class=\"" + cls + "\"") if cls else ""}>{h}</p>\n<!-- /wp:paragraph -->'

def b_titre(texte, niv=2):
    texte = esc(texte)
    a = '' if niv == 2 else ' ' + j({'level': niv})
    return f'<!-- wp:heading{a} -->\n<h{niv} class="wp-block-heading">{texte}</h{niv}>\n<!-- /wp:heading -->'

def b_liste(items_html, ordonnee=False, style=''):
    if not items_html:
        return ''
    a = {}
    if ordonnee: a['ordered'] = True
    if style: a['className'] = style
    tag = 'ol' if ordonnee else 'ul'
    cls = 'wp-block-list' + (' ' + style if style else '')
    lis = '\n\n'.join(f'<!-- wp:list-item -->\n<li>{h}</li>\n<!-- /wp:list-item -->' for h in items_html)
    return f'<!-- wp:list{(" " + j(a)) if a else ""} -->\n<{tag} class="{cls}">{lis}</{tag}>\n<!-- /wp:list -->'

def b_tableau(t):
    lignes = t.find_all('tr')
    if not lignes:
        return ''
    th = lignes[0].find_all('th')
    tete = ''
    if th:
        tete = '<thead><tr>' + ''.join(f'<th scope="col">{inline_html(c)}</th>' for c in th) + '</tr></thead>'
        lignes = lignes[1:]
    corps = ''.join('<tr>' + ''.join(f'<td>{inline_html(c)}</td>' for c in tr.find_all(['td', 'th'])) + '</tr>' for tr in lignes)
    return f'<!-- wp:table -->\n<figure class="wp-block-table"><table class="has-fixed-layout">{tete}<tbody>{corps}</tbody></table></figure>\n<!-- /wp:table -->'

class Contexte:
    """Informations collectées pendant la conversion d'une page."""
    def __init__(self, titre):
        self.titre = titre
        self.images = []
        self.contacts = []
        self.alertes = []
        self.notes = []

def img_bloc(url, alt='', legende='', taille='large'):
    jeton = 'VFIMG' + hashlib.md5(url.encode()).hexdigest()[:10]
    fig = f'<figure class="wp-block-image size-{taille}"><img src="{url}" alt="{html.escape(alt or "")}" class="wp-image-{jeton}"/>' + (f'<figcaption class="wp-element-caption">{esc(legende)}</figcaption>' if legende else '') + '</figure>'
    return f'<!-- wp:image {{"id":{jeton},"sizeSlug":"{taille}","linkDestination":"none"}} -->\n{fig}\n<!-- /wp:image -->'

def html_vers_blocs(h, ctx, niveau_titres=None):
    """Transforme un fragment HTML en blocs. Les paragraphes qui ne contiennent qu'un lien vers un document deviennent des documents."""
    corps = nettoie(h)
    blocs, docs = [], []
    def vider_docs():
        if docs:
            blocs.append(b_documents(list(docs))); docs.clear()
    # Le HTML d'Elementor met souvent du texte nu entre des balises : on l'emballe dans des <p>.
    elements = []
    tampon = []
    for c in list(corps.children):
        if isinstance(c, NavigableString) or (isinstance(c, Tag) and c.name in ('a', 'strong', 'em', 'br', 'sup', 'sub', 'img')):
            tampon.append(c)
        else:
            if tampon:
                elements.append(('inline', tampon)); tampon = []
            elements.append(('bloc', c))
    if tampon:
        elements.append(('inline', tampon))

    for genre, el in elements:
        if genre == 'inline':
            frag = BeautifulSoup('<p></p>', 'lxml').p
            for c in el:
                frag.append(c.extract() if hasattr(c, 'extract') else c)
            el = frag
        if not isinstance(el, Tag):
            continue
        n = el.name
        if n == 'p' or genre == 'inline':
            # Un paragraphe = un lien vers un document -> liste de documents
            liens = el.find_all('a')
            texte = texte_propre(el.get_text())
            if len(liens) == 1 and est_doc(liens[0].get('href', '')) and len(texte) <= len(texte_propre(liens[0].get_text())) + 3:
                docs.append((texte_propre(liens[0].get_text()) or 'Document', liens[0]['href']))
                continue
            vider_docs()
            for im in el.find_all('img'):
                blocs.append(img_bloc(im['src'], im.get('alt', '')))
                im.decompose()
            # Paragraphes séparés par des <br><br> : on garde des lignes
            blocs.append(b_paragraphe(inline_html(el)))
        elif n in ('h1', 'h2', 'h3', 'h4', 'h5', 'h6'):
            vider_docs()
            t = texte_propre(el.get_text())
            if t and t.lower() != (ctx.titre or '').lower():
                niv = {'h1': 2, 'h2': 2, 'h3': 3, 'h4': 3, 'h5': 4, 'h6': 4}[n] if not niveau_titres else niveau_titres
                blocs.append(b_titre(t, niv))
        elif n in ('ul', 'ol'):
            items = [inline_html(li) for li in el.find_all('li', recursive=False)]
            items = [i for i in items if texte_propre(BeautifulSoup(i, 'lxml').get_text())]
            # Liste de liens vers des documents -> documents
            lis = el.find_all('li', recursive=False)
            if lis and all(len(li.find_all('a')) == 1 and est_doc(li.a.get('href', '')) for li in lis):
                for li in lis:
                    docs.append((texte_propre(li.get_text()) or 'Document', li.a['href']))
                continue
            vider_docs()
            blocs.append(b_liste(items, ordonnee=(n == 'ol')))
        elif n == 'table':
            vider_docs(); blocs.append(b_tableau(el))
        elif n == 'blockquote':
            vider_docs()
            blocs.append(f'<!-- wp:quote -->\n<blockquote class="wp-block-quote">{b_paragraphe(inline_html(el))}</blockquote>\n<!-- /wp:quote -->')
        elif n == 'img':
            vider_docs(); blocs.append(img_bloc(el['src'], el.get('alt', '')))
    vider_docs()
    return [b for b in blocs if b]

# ---------------------------------------------------------------------------
# 3. Blocs Villefontaine
# ---------------------------------------------------------------------------
def type_doc(url):
    return url.split('?')[0].rsplit('.', 1)[-1].upper()

def b_documents(items, titre=''):
    docs = [{'titre': texte_propre(t)[:160], 'url': u} for t, u in items]
    a = {'documents': docs}
    if titre: a['titre'] = titre
    return f'<!-- wp:vf/documents {j(a)} /-->'

def b_chiffres(items):
    return f'<!-- wp:vf/chiffres {j({"chiffres": items})} /-->'

def b_cartes(items, colonnes=3):
    return f'<!-- wp:vf/cartes-liens {j({"cartes": items, "colonnes": colonnes})} /-->'

def b_contact(d):
    return f'<!-- wp:vf/contact-carte {j(d)} /-->'

# Titres des vidéos de l'ancien site (titre d'iframe pertinent, RGAA 2.2).
TITRES_VIDEOS = {
    'yxhFpFMDVCw': 'Le jardin collectif de Vaugelas',
    'rHsVSqEwB8M': 'The Village, le French Outlet (vidéo de la Compagnie de Phalsbourg)',
}

def b_video(url, titre=''):
    if not titre or titre.strip().lower() == 'vidéo':
        titre = next((t for k, t in TITRES_VIDEOS.items() if k in url), titre)
    return f'<!-- wp:vf/video {j({"url": url, "titre": titre})} /-->'

def b_details(titre, blocs_internes):
    inner = '\n\n'.join(blocs_internes) or b_paragraphe('')
    return f'<!-- wp:details -->\n<details class="wp-block-details"><summary>{esc(titre)}</summary>{inner}</details>\n<!-- /wp:details -->'

def b_a_savoir(titre, blocs_internes):
    t = b_titre(titre, 3) if titre else ''
    inner = '\n\n'.join([x for x in [t] + blocs_internes if x])
    return f'<!-- wp:group {{"className":"is-style-a-savoir","layout":{{"type":"default"}}}} -->\n<div class="wp-block-group is-style-a-savoir">{inner}</div>\n<!-- /wp:group -->'

def b_boutons(items):
    bs = '\n\n'.join(f'<!-- wp:button{(" " + j({"className": "is-style-secondaire"})) if i else ""} -->\n<div class="wp-block-button{" is-style-secondaire" if i else ""}"><a class="wp-block-button__link wp-element-button" href="{html.escape(u)}">{esc(t)}</a></div>\n<!-- /wp:button -->' for i, (t, u) in enumerate(items))
    return f'<!-- wp:buttons -->\n<div class="wp-block-buttons">{bs}</div>\n<!-- /wp:buttons -->'

def contact_de(d):
    """Widget « contact » d'OceanWP -> champs de l'encart contact."""
    def g(k):
        return texte_propre(BeautifulSoup(str(d.get(k, '') or ''), 'lxml').get_text())
    return {
        'titre': g('title'),
        'texte': g('text'),
        'adresse': g('address'),
        'tel': g('phone') or g('mobile'),
        'courriel': g('email'),
        'lien': g('web') if g('web').startswith('http') else '',
    }

# ---------------------------------------------------------------------------
# 4. Nœuds -> blocs d'une page
# ---------------------------------------------------------------------------
def convertir_noeuds(noeuds, ctx):
    blocs = []
    boutons, docs, chiffres, cartes = [], [], [], []
    # Niveau des titres Elementor : le plus grand niveau utilisé devient h2.
    niveaux = sorted({int(n['niv'][1]) for n in noeuds if n['t'] == 'titre' and str(n.get('niv', 'h2'))[1:].isdigit()})
    def niv(n):
        try:
            k = int(n.get('niv', 'h2')[1])
        except Exception:
            return 2
        return 2 if (not niveaux or k == niveaux[0]) else 3
    def vider():
        if docs:
            blocs.append(b_documents(list(docs))); docs.clear()
        if boutons:
            blocs.append(b_boutons(list(boutons))); boutons.clear()
        if chiffres:
            blocs.append(b_chiffres(list(chiffres))); chiffres.clear()
        if cartes:
            blocs.append(b_cartes(list(cartes), 3 if len(cartes) != 4 else 2)); cartes.clear()
    for n in noeuds:
        t = n['t']
        if t == 'bouton':
            if not n['url'] or not n['texte']:
                continue
            if est_doc(n['url']):
                docs.append((n['texte'], n['url']))
            else:
                if docs or chiffres or cartes:
                    d = list(boutons); vider(); boutons.extend(d)
                boutons.append((n['texte'], n['url']))
            continue
        if t == 'chiffre':
            if boutons or docs or cartes:
                vider()
            chiffres.append({'nombre': (n['avant'] or '') + n['nombre'] + (n['apres'] or ''), 'libelle': texte_propre(n['libelle'])})
            continue
        if t == 'carte':
            if boutons or docs or chiffres:
                vider()
            if n['titre'] or n['image']:
                cartes.append({'titre': texte_propre(n['titre']), 'texte': n['texte'], 'url': n['url'], 'image': n['image']})
            continue
        vider()
        if t == 'titre':
            if n['texte'] and n['texte'].lower() != (ctx.titre or '').lower() and len(n['texte']) < 160:
                blocs.append(b_titre(n['texte'], niv(n)))
        elif t == 'html':
            blocs.extend(html_vers_blocs(n['html'], ctx))
        elif t == 'plis':
            for titre, contenu in n['items']:
                if not titre:
                    continue
                titre_t = texte_propre(BeautifulSoup(titre, 'lxml').get_text())
                inner = html_vers_blocs(contenu, ctx, niveau_titres=4)
                # Volet qui ne contient qu'un document : il rejoint la liste de documents.
                if len(inner) == 1 and inner[0].startswith('<!-- wp:vf/documents'):
                    d = json.loads(re.search(r'\{.*\}', inner[0], re.S).group(0))
                    for doc in d['documents']:
                        docs.append((titre_t if len(d['documents']) == 1 else doc['titre'], doc['url']))
                    continue
                if docs:
                    blocs.append(b_documents(list(docs))); docs.clear()
                blocs.append(b_details(titre_t, inner))
        elif t == 'alerte':
            paras = [b_paragraphe(esc(l)) for l in (n['texte'] or '').split('\n') if l.strip()]
            blocs.append(b_a_savoir(n['titre'], paras))
        elif t == 'contact':
            ctx.contacts.append(contact_de(n['d']))
        elif t == 'image':
            blocs.append(img_bloc(n['url'], n['alt'], n.get('legende', '')))
        elif t == 'galerie':
            imgs = '\n\n'.join(img_bloc(u, '', '', 'large') for u in n['urls'][:24])
            blocs.append(f'<!-- wp:gallery {{"linkTo":"none"}} -->\n<figure class="wp-block-gallery has-nested-images columns-default is-cropped">{imgs}</figure>\n<!-- /wp:gallery -->')
        elif t == 'liste':
            items = []
            for texte, url in n['items']:
                if not texte:
                    continue
                items.append(f'<a href="{html.escape(url)}">{esc(texte)}</a>' if url else esc(texte))
            blocs.append(b_liste(items, style='is-style-coches'))
        elif t == 'video':
            if n['url']:
                blocs.append(b_video(n['url']))
        elif t == 'citation':
            blocs.append(f'<!-- wp:quote -->\n<blockquote class="wp-block-quote">{b_paragraphe(esc(texte_propre(BeautifulSoup(n["texte"], "lxml").get_text())))}' + (f'<cite>{esc(n["auteur"])}</cite>' if n['auteur'] else '') + '</blockquote>\n<!-- /wp:quote -->')
        elif t == 'tarifs':
            lignes = ''.join(f'<tr><td>{esc(a)}</td><td>{esc(b)}</td></tr>' for a, b, c in n['items'])
            blocs.append(f'<!-- wp:table -->\n<figure class="wp-block-table"><table class="has-fixed-layout"><thead><tr><th>Prestation</th><th>Tarif</th></tr></thead><tbody>{lignes}</tbody></table></figure>\n<!-- /wp:table -->')
        elif t == 'formulaire':
            ctx.notes.append('formulaire Elementor non repris')
        elif t == 'html_brut':
            ctx.notes.append('code HTML spécifique non repris')
    vider()
    return [b for b in blocs if b]
