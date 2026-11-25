# -*- coding: utf-8 -*-
"""
Méga-menu : construit à partir du plan (plan.py), sans dépendre de l'export de l'ancien site.

Utilisé par construire.py, et lancé seul (python3 menu.py) pour mettre à jour le menu et les pages en attente
dans pages.json quand seul le plan a changé.
"""
import json
import plan


def dans_le_menu(options):
    """Une page est dans le menu si elle est publiée et pas marquée hors menu."""
    return not options.get('hors_menu') and options.get('statut', 'publish') == 'publish'


def construire_menu():
    menu = []
    for rcle, rtitre, rdesc, enfants in plan.RUBRIQUES:
        rang2 = []
        for (c, t, s, src, o) in enfants:
            if not dans_le_menu(o):
                continue
            rang3 = [{'titre': o3.get('menu_titre', t3), 'cle': c3} for (c3, t3, s3, src3, o3) in o.get('enfants', []) if dans_le_menu(o3)]
            for lien in o.get('liens_menu', []):
                rang3.insert(lien.get('position', len(rang3)), {'titre': lien['titre'], 'cle': lien['cle']})
            rang2.append({'titre': o.get('menu_titre', t), 'cle': c, 'enfants': rang3})
        menu.append({'titre': rtitre, 'cle': rcle, 'description': rdesc, 'enfants': rang2})
    return menu


def pages_en_attente():
    """Pages prévues par le plan mais sans contenu : un brouillon avec une note pour les agents."""
    pages = []
    for rcle, rtitre, rdesc, enfants in plan.RUBRIQUES:
        for (c, t, s, src, o) in enfants:
            for i, (c3, t3, s3, src3, o3) in enumerate(o.get('enfants', [])):
                if o3.get('attente'):
                    pages.append({
                        'cle': c3, 'titre': t3, 'slug': s3, 'parent': c, 'ordre': i + 1, 'extrait': o3.get('extrait', ''),
                        'contenu': '<!-- wp:paragraph -->\n<p>Page prévue par le plan du site, en attente du contenu fourni par la mairie. Elle est en brouillon : elle n’apparaît ni sur le site ni dans le menu. Une fois le texte saisi, publiez-la puis ajoutez-la au menu (Apparence &gt; Menus).</p>\n<!-- /wp:paragraph -->',
                        'image': '', 'gabarit': '', 'contact': {}, 'statut': 'draft', 'icone': '',
                    })
    return pages


if __name__ == '__main__':
    lot = json.load(open('pages.json', encoding='utf-8'))
    lot['menu'] = construire_menu()
    connues = {p['cle'] for p in lot['pages']}
    ajoutees = [p for p in pages_en_attente() if p['cle'] not in connues]
    lot['pages'] += ajoutees
    json.dump(lot, open('pages.json', 'w', encoding='utf-8'), ensure_ascii=False)
    n = lambda e: 1 + sum(n(x) for x in e.get('enfants', []))
    print('menu :', ', '.join(f"{r['titre']} {n(r) - 1}" for r in lot['menu']), '; pages en attente ajoutées :', [p['cle'] for p in ajoutees])
