# -*- coding: utf-8 -*-
"""Page « Signalement » : nouvelle page (sans équivalent sur l'ancien site), demandée par Ju le 24/11/2026.

Les liens « Faire un signalement » (panneau Vos démarches) et « Signalement » (accès rapides de l'accueil)
pointent vers /signalement/. Module autonome, comme urbanisme.py.
"""
from urbanisme import attrs, p, titre, a_savoir

# Adresses de réception : fictives tant que la mairie n'a pas indiqué qui reçoit les signalements
# (même règle que pour la page Contact : vraies adresses et envoi SMTP réglés à la mise en ligne).
ADRESSE = 'signalement@example.org'
NATURES = [
    'Voirie et trottoirs',
    'Éclairage public',
    'Propreté et dépôts sauvages',
    'Espaces verts et arbres',
    'Mobilier urbain et aires de jeux',
    'Autre signalement',
]
EXTRAIT = 'Un problème sur l’espace public ? Signalez-le à la mairie en quelques clics.'


def signalement(ATTACH=None):
    """Blocs de la page, dans l'ordre."""
    formulaire = {
        'modele': 'signalement',
        'titre': 'Votre signalement',
        'destinataires': [{'libelle': n, 'courriel': ADRESSE} for n in NATURES],
    }
    return [
        p('Un trou dans la chaussée, un lampadaire en panne, un dépôt sauvage, un jeu abîmé ? Décrivez le problème et indiquez où il se trouve : votre signalement est transmis au service concerné.'),
        a_savoir([
            titre('En cas d’urgence'),
            p('Ce formulaire n’est pas suivi en temps réel. En cas de danger immédiat, appelez le <strong>17</strong> (police), le <strong>18</strong> (pompiers) ou le <strong>112</strong>.'),
        ]),
        '<!-- wp:vf/formulaire-contact %s /-->' % attrs(formulaire),
    ]


def contenu_page():
    return '\n\n'.join(signalement())


if __name__ == '__main__':
    print(contenu_page())
