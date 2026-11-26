# -*- coding: utf-8 -*-
"""
Classement des événements par thématique, quand l'ancien site ne le donnait pas.

La page « Les grands rendez-vous » n'affiche que les événements des thématiques « Culture et patrimoine » et « Sport »
(décision de Ju du 26/11/2026). Sur l'ancien site, presque aucun événement n'avait de thématique : ceux qui relèvent
clairement de la culture ou du sport sont classés ici, par leur clé de migration. La mairie complétera dans l'admin.

- python3 thematiques.py : met à jour contenus.json ;
- construire.py l'appelle aussi après avoir produit le lot.
"""
import json

# Clé de migration de l'événement -> thématiques à lui ajouter (nom exact de la thématique).
CLASSEMENT = {
    'evt-39903': ['Culture et patrimoine'],  # Exposition Trépid
    'evt-40119': ['Culture et patrimoine'],  # Heure de la musique : Le Rêve
    'evt-40131': ['Culture et patrimoine'],  # Le petit chaperon rouge qui voit tout noir
    'evt-40164': ['Culture et patrimoine'],  # Après-midi cirque
}


def appliquer(evenements):
    faits = []
    for e in evenements:
        for theme in CLASSEMENT.get(e['cle'], []):
            if theme not in e['themes']:
                e['themes'].append(theme)
            faits.append(e['titre'])
    return faits


if __name__ == '__main__':
    lot = json.load(open('contenus.json', encoding='utf-8'))
    faits = appliquer(lot['evenements'])
    json.dump(lot, open('contenus.json', 'w', encoding='utf-8'), ensure_ascii=False)
    print(len(faits), 'événements classés :', faits)
