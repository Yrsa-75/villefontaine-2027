# -*- coding: utf-8 -*-
"""
Plan de la nouvelle arborescence (document « Refonte villefontaine.fr : arborescence et menu », validé le 27/10/2026).

Chaque page : (clé, titre, slug, sources, options)
- sources : slugs des pages actuelles dont le contenu est repris (dans l'ordre) ;
- options : extrait (chapô), icone, agenda (slug de thématique ou True), contenu_avant/apres, sections (titres de section
  pour des sources fusionnées), statut, image, description (méga-menu), demarches (page d'accordéons).

Menu (document « ARCHITECTURE SITE NEW » transmis par Ju le 25/11/2026, qui fait foi) :
- hors_menu : la page reste en ligne (listée dans sa rubrique, trouvée par la recherche) mais n'est pas dans le méga-menu ;
- menu_titre : intitulé du méga-menu quand il diffère du titre de la page ;
- liens_menu : liens supplémentaires vers une page rangée ailleurs ({titre, cle, position}) ;
- statut 'draft' + attente : page prévue par le plan, en brouillon tant que la mairie n'a pas fourni le contenu (hors menu).

Pages recomposées à la main (contenus en bleu du même document) : leur contenu est décrit dans recompositions.py.
"""

RUBRIQUES = [
    ('la-mairie', 'La mairie', 'Vos élus, le budget, les services municipaux et la vie démocratique.', [
        ('services-municipaux', 'Services municipaux', 'services-municipaux', ['presentation-mairie-services-entites', 'contacter-services-municipaux'], {'extrait': 'Les services de la mairie, leurs missions et comment les joindre.', 'carte': 'Services municipaux'}),
        ('conseil-municipal', 'Conseil municipal', 'conseil-municipal', ['conseil-municipal'], {'extrait': 'Composition, séances et décisions du conseil municipal.', 'enfants': [
            ('elus', 'Les élus', 'les-elus', ['elus'], {'extrait': 'Le maire, les adjoints et les conseillers municipaux.'}),
            ('engagements', 'Nos engagements', 'nos-engagements', ['nos-engagements'], {}),
            ('expression-groupes', 'Expression des groupes', 'expression-des-groupes', ['expressions-politiques'], {'extrait': 'Les tribunes des groupes politiques du conseil municipal.'}),
            ('instances-citoyennes', 'Instances citoyennes', 'instances-citoyennes', ['les-instances-citoyennes', 'conseil-citoyen'], {'hors_menu': True, 'sections': ['Les instances citoyennes', 'Le conseil citoyen']}),
            ('cme', 'Conseil municipal des enfants', 'conseil-municipal-des-enfants', ['conseil-municipal-des-enfants'], {'extrait': 'Les jeunes élus de la ville et leurs projets.'}),
            ('joindre-elus', 'Joindre les élus', 'joindre-les-elus', [], {'statut': 'draft', 'attente': True, 'extrait': 'Prendre rendez-vous ou écrire à un élu.'}),
        ]}),
        # Budget : 6 rectangles dans la page (document du 25/11/2026), contenu décrit dans recompositions.py.
        ('budget', 'Budget de la commune', 'budget', ['budget'], {'extrait': 'Pour bien comprendre le budget et les investissements de la ville.', 'enfants': [
            ('budget-2026', 'Budget 2026', 'budget-2026', [], {'hors_menu': True, 'icone': 'calendrier'}),
            ('budget-2025', 'Budget 2025', 'budget-2025', [], {'hors_menu': True, 'icone': 'calendrier'}),
            ('budget-2024', 'Budget 2024', 'budget-2024', [], {'hors_menu': True, 'icone': 'calendrier'}),
            ('budget-2023', 'Budget 2023', 'budget-2023', [], {'hors_menu': True, 'icone': 'calendrier'}),
            ('budget-2022', 'Budget 2022', 'budget-2022', [], {'hors_menu': True, 'icone': 'calendrier'}),
            ('budget-chiffres-cles', 'Les chiffres clés', 'chiffres-cles', [], {'hors_menu': True, 'icone': 'info'}),
        ]}),
        ('budget-participatif', 'Budget participatif', 'budget-participatif', ['budget-participatif-2-0'], {'extrait': 'Proposez et soutenez des projets pour votre quartier.'}),
        ('jumelage', 'Jumelage', 'jumelage', ['jumelages-internationaux'], {'extrait': 'Kahl am Main, Gremda, Wolfen-Bitterfeld et Salzano : nos quatre villes jumelles.'}),
        ('marches-publics', 'Marchés publics', 'marches-publics', ['marches-publics'], {'extrait': 'Les consultations en cours de la ville et l’inscription aux alertes pour les entreprises.'}),
        ('recrutement', 'Villefontaine recrute', 'villefontaine-recrute', ['recrutement-ville', 'candidature-spontanee'], {'sections': ['Nos offres d’emploi', 'Candidature spontanée'], 'extrait': 'Offres d’emploi de la ville, candidature spontanée, stages et apprentissage.'}),
        ('affichage-legal', 'Affichage légal', 'affichage-legal', ['affichage-legal', 'arretes-municipaux'], {'sections': ['Délibérations et publications', 'Arrêtés municipaux'], 'extrait': 'Délibérations, publications officielles et arrêtés municipaux.'}),
    ]),
    ('vie-quotidienne', 'Vie quotidienne', 'École, jeunesse, seniors, santé, emploi : les services de tous les jours.', [
        ('enfance-famille', 'Enfance, famille', 'enfance-famille', [], {'extrait': 'Scolarité, restauration scolaire, accueil de loisirs et petite enfance.', 'enfants': [
            ('scolarite', 'Scolarité', 'scolarite', ['scolarite'], {}),
            ('restauration-scolaire', 'Restauration scolaire et accueil de loisirs', 'restauration-scolaire', ['restauration-scolaire'], {}),
            ('petite-enfance', 'Petite enfance', 'petite-enfance', ['petite-enfance'], {}),
        ]}),
        ('jeunesse', 'Jeunesse', 'jeunesse', [], {'extrait': 'Information jeunesse et Maisons pour tous.', 'enfants': [
            ('info-jeunes', 'Info Jeunes', 'info-jeunes', ['infojeunes'], {}),
            ('maisons-pour-tous', 'Maisons pour tous', 'maisons-pour-tous', ['maisons-pour-tous'], {'extrait': 'Les maisons pour tous Florence Arthaud et Denise Tronchet : horaires et services.'}),
        ]}),
        ('seniors', 'Seniors', 'seniors', ['animations-et-associations-retraites'], {'extrait': 'Activités, accompagnement et services à domicile pour les Villards de plus de 60 ans.', 'agenda': 'seniors', 'enfants': [
            ('le-phare', 'Le Phare', 'le-phare', ['phare-senior-ccas'], {}),
            ('le-voltaire', 'Le Voltaire', 'le-voltaire', ['voltaire'], {}),
            ('portage-repas', 'Portage de repas et téléalarme', 'portage-de-repas', ['portage-de-repas-a-domicile', 'inscription-tele-alarme'], {'menu_titre': 'Portage de repas à domicile', 'sections': ['Portage de repas à domicile', 'Téléalarme']}),
            ('plan-canicule', 'Plan canicule', 'plan-canicule', [], {'statut': 'draft', 'attente': True, 'extrait': 'Le registre des personnes vulnérables et les bons réflexes en cas de fortes chaleurs.'}),
        ]}),
        ('mobilite', 'Mobilité et stationnement', 'mobilite', [], {'extrait': 'Vélo, covoiturage, transports en commun et stationnement.', 'enfants': [
            ('mobilites-douces', 'Mobilités douces et plan vélo', 'mobilites-douces', ['mode-doux', 'plan-velo'], {'sections': ['Les mobilités douces', 'Le plan vélo']}),
            ('covoiturage', 'Covoiturage', 'covoiturage', ['plateformes-locales-covoiturage'], {}),
            ('transports-en-commun', 'Transports en commun', 'transports-en-commun', ['transports-en-commun', 'acces-villefontaine'], {'sections': ['Les transports en commun', 'Venir à Villefontaine']}),
            ('stationnement', 'Stationnement gratuit', 'stationnement', ['stationnement'], {}),
        ]}),
        ('sante-solidarite', 'Santé et solidarité', 'sante-solidarite', [], {'extrait': 'CCAS, action sociale, pouvoir d’achat, complémentaire santé.', 'enfants': [
            ('ccas', 'CCAS et action sociale', 'ccas-action-sociale', ['missions-ccas'], {}),
            ('maison-pouvoir-achat', 'Maison du pouvoir d’achat', 'maison-du-pouvoir-d-achat', ['maison-pouvoir-achat'], {'hors_menu': True, }),
            ('complementaire-sante', 'Complémentaire santé', 'complementaire-sante', ['la-complementaire-sante-des-villards'], {'extrait': 'Ma commune ma santé : une complémentaire santé mutualisée pour faire des économies.'}),
            ('indicateurs', 'Qualité de l’eau et de l’air', 'qualite-eau-air', ['indicateurs'], {}),
            ('handicap', 'Handicap', 'handicap', [], {'statut': 'draft', 'attente': True, 'extrait': 'Les aides, les démarches et les interlocuteurs pour les personnes en situation de handicap.'}),
        ]}),
        ('emploi', 'Économie, emploi et insertion', 'emploi-insertion', [], {'liens_menu': [{'titre': 'Villefontaine recrute', 'cle': 'recrutement', 'position': 0}], 'extrait': 'Mission locale, relais emploi du CCAS, chantier d’insertion.', 'enfants': [
            ('structures-emploi', 'Mission locale et structures emploi', 'mission-locale', ['structures-emploi'], {}),
            ('chantier-insertion', 'Chantier d’insertion', 'chantier-insertion', ['chantier-environnement-insertion'], {'hors_menu': True, }),
            ('relais-emploi', 'Relais emploi du CCAS', 'relais-emploi-ccas', [], {'statut': 'draft', 'attente': True, 'extrait': 'Un accompagnement de proximité pour votre recherche d’emploi.'}),
        ], 'cartes': [{'titre': 'Villefontaine recrute', 'texte': 'Les offres d’emploi de la ville.', 'cle': 'recrutement', 'icone': 'citoyen'}]}),
        ('commerces', 'Commerces et marchés', 'commerces', [], {'extrait': 'S’implanter, zones d’activité, commerce de proximité, marchés, The Village.', 'enfants': [
            ('s-implanter', 'S’implanter à Villefontaine', 's-implanter', ['implanter-villefontaine', 'le-patio-coworking'], {'sections': ['S’implanter à Villefontaine', 'Le Patio, espace de coworking']}),
            ('zones-activite', 'Zones d’activité', 'zones-activite', ['les-zones-dactivite-economique'], {'menu_titre': 'Zones d’activités économiques', }),
            ('commerce-proximite', 'Commerce de proximité et marchés', 'commerce-de-proximite', ['commercants-villards', 'les-marches'], {'menu_titre': 'Commerce de proximité', 'sections': ['Les commerçants villards', 'Les marchés']}),
            ('the-village', 'The Village', 'the-village', ['the-village'], {'menu_titre': 'The Village outlet', }),
        ]}),
        ('services-publics', 'Services publics', 'services-publics', ['accompagnement'], {'extrait': 'France Travail, La Poste, impôts, PIMMS : les services publics près de chez vous.'}),
    ]),
    ('cadre-de-vie', 'Cadre de vie', 'Sécurité, logement, urbanisme, propreté et grands projets de la ville.', [
        ('tranquillite', 'Tranquillité et sécurité', 'tranquillite', [], {'extrait': 'Police municipale, gendarmerie, ville vigilante, justice et prévention des risques.', 'enfants': [
            ('police-gendarmerie', 'Police municipale et gendarmerie', 'police-gendarmerie', ['police-municipale', 'gendarmerie'], {'sections': ['La police municipale', 'La gendarmerie']}),
            ('ville-vigilante', 'Ville vigilante', 'ville-vigilante', ['ville-vigilante', 'inscription-voisins-vigilants'], {}),
            ('maison-justice', 'Maison de la justice et du droit', 'maison-justice-droit', ['maison-justice-droit'], {}),
            ('risques-majeurs', 'Risques majeurs', 'risques-majeurs', ['risques-majeurs'], {'hors_menu': True, }),
        ]}),
        ('logement', 'Logement et habitat', 'logement', [], {'extrait': 'S’installer, logement social, énergie.', 'enfants': [
            ('s-installer', 'S’installer à Villefontaine', 's-installer', ['nouveaux-arrivants'], {'extrait': 'Cérémonie d’accueil, kit de bienvenue et démarches d’emménagement.'}),
            ('logement-social', 'Logement social', 'logement-social', ['demande-de-logement-social'], {'hors_menu': True, }),
            ('energie', 'Énergie : infos et conseils', 'energie', ['point-info-energie'], {'hors_menu': True, }),
            ('enedis-semidao', 'Infos Enedis et Semidao', 'infos-enedis-semidao', [], {'statut': 'draft', 'attente': True, 'extrait': 'Coupures, travaux et informations sur les réseaux d’électricité et d’eau.'}),
        ]}),
        ('urbanisme', 'Urbanisme', 'urbanisme', [], {'extrait': 'Plan local d’urbanisme, démarches et occupation du domaine public.', 'enfants': [
            ('plu', 'Plan local d’urbanisme (PLU)', 'plu', ['plu'], {}),
            ('demarches-urbanisme', 'Démarches d’urbanisme', 'demarches-urbanisme', ['demarches-urbanisme'], {}),
            ('domaine-public', 'Occupation du domaine public', 'occupation-domaine-public', ['redevance-doccupation-du-domaine-public', 'demande-occupation-domaine-public-pour-chantier-prive'], {'sections': ['Redevance d’occupation du domaine public', 'Chantier privé sur le domaine public'], 'extrait': 'Terrasses, chantiers, étalages : demandes d’autorisation et redevance.'}),
        ]}),
        ('proprete', 'Propreté et déchets', 'proprete-dechets', [], {'extrait': 'Déchèteries et propreté de la ville.', 'enfants': [
            ('decheteries', 'Déchèteries', 'decheteries', ['decheteries'], {}),
            ('la-collecte', 'La collecte', 'la-collecte', [], {'statut': 'draft', 'attente': True, 'extrait': 'Jours de collecte, tri et encombrants.'}),
        ]}),
        ('accessibilite-ville', 'Accessibilité', 'accessibilite', ['commission-accessibilite'], {}),
        ('grands-projets', 'Les grands projets', 'grands-projets', [], {'extrait': 'Les projets qui transforment Villefontaine.', 'enfants': [
            ('coeur-de-ville', 'Cœur de ville', 'coeur-de-ville', ['grand-projet-coeur-de-ville'], {'hors_menu': True, }),
            ('renovation-urbaine', 'La rénovation urbaine', 'renovation-urbaine', ['la-renovation-urbaine'], {'hors_menu': True, }),
            ('campus', 'Campus de la construction durable', 'campus-construction-durable', ['campus-construction-durable'], {'hors_menu': True, }),
        ]}),
        ('patrimoine', 'Patrimoine historique et naturel', 'patrimoine', ['histoire-ville', 'reserve-naturelle-etangs-forets', 'sites-remarquables', 'visiter-la-ville-autrement'], {'sections': ['L’histoire de la ville', 'La réserve naturelle', 'Les sites remarquables', 'Visiter la ville autrement'], 'enfants': [
            ('nuisibles', 'Nuisibles et espèces invasives', 'nuisibles-invasifs', ['lutte-contre-les-nuisibles-invasifs'], {'hors_menu': True, }),
        ]}),
    ]),
    ('sortir-bouger', 'Sortir et bouger', 'Culture, sport, nature et associations : profitez de votre ville.', [
        ('culture', 'Vie culturelle', 'culture', [], {'extrait': 'Médiathèque, théâtre, cinéma et art urbain.', 'enfants': [
            ('mediatheque', 'Médiathèque et bibliothèques', 'mediatheque', ['lecture'], {}),
            ('theatre-cinema', 'Théâtre et cinéma', 'theatre-cinema', ['le-vellein-scenes-capi', 'cinema'], {'sections': ['Le Vellein, scènes de la CAPI', 'Le cinéma']}),
            ('street-art', 'Parcours street art', 'street-art', ['parcours-street-art'], {}),
        ]}),
        ('sports', 'Sports et loisirs', 'sports-loisirs', ['equipements-sportifs', 'sport-nature', 'parcours-fitness-plein-air'], {'sections': ['Les équipements sportifs', 'Le sport nature', 'Le parcours fitness'], 'carte': 'Équipements sportifs'}),
        ('jardins', 'Jardins et espaces verts', 'jardins-espaces-verts', ['jardin-solidaire-vaugelas'], {}),
        ('grands-rdv', 'Les grands rendez-vous', 'grands-rendez-vous', ['saison-culturelle'], {'menu_titre': 'Les grands rendez-vous sportifs et culturels', 'agenda': True}),
        ('associations', 'Associations', 'associations', ['les-associations', 'demande-de-subvention', 'organiser-une-manifestation'], {'sections': ['Les associations villardes', 'Demander une subvention', 'Promouvoir votre événement']}),
    ]),
]

# Espace « Démarches » : chaque source devient un volet dépliable.
DEMARCHES = ('demarches', 'Vos démarches', 'Toutes les démarches administratives, en ligne ou en mairie.', [
    ('etat-civil', 'État civil', 'etat-civil', ['acte-de-naissance', 'demande-acte-mariage', 'mariage-villefontaine', 'demande-acte-deces', 'pacs', 'parrainage-civil', 'changer-de-prenom', 'changement-de-nom'], 'Naissance, mariage, décès, PACS, parrainage civil, changement de prénom ou de nom.', 'famille'),
    ('identite-citoyennete', 'Identité et citoyenneté', 'identite-citoyennete', ['demande-cni-ou-passeport', 'demande-inscription-listes-electorales', 'recensement-militaire', 'permis-conduire-carte-grise', 'attestation-daccueil'], 'Carte d’identité, passeport, listes électorales, recensement, permis, attestation d’accueil.', 'citoyen'),
    ('vie-pratique', 'Vie pratique', 'vie-pratique', ['organiser-vide-grenier', 'organiser-vide-maison', 'demande-dautorisation-de-stationnement-pour-un-demenagement-realise-par-un-particulier', 'operation-tranquillite-vacances', 'declaration-de-chien-dangereux'], 'Vide-grenier, vide-maison, déménagement, tranquillité vacances, chien dangereux.', 'maison'),
    ('professionnels', 'Professionnels', 'professionnels', ['faire-connaitre-votre-activite', 'demande-terrasse-etalage', 'demande-emplacement-food-truck-hors-marches', 'declaration-vente-deballage', 'demande-autorisation-stationnement-taxi', 'demande-debits-boissons-permanent', 'demande-registre-police', 'demande-emplacement-marche-plein-air', 'demande-acces-pieton-centre-ville'], 'Terrasse, food-truck, vente au déballage, taxi, débit de boissons, marchés.', 'idee'),
    ('reserver-salle', 'Réserver une salle', 'reserver-une-salle', ['reserver-espace-france-gall', 'reserver-salle-daniel-balavoine', 'reserver-salle-didier-latimier', 'reserver-salle-henri-bonnet', 'reserver-salle-pierre-coignard', 'reserver-salle-sadi-desquesnes'], 'Les salles municipales à louer et comment les réserver.', 'asso'),
    ('cimetiere', 'Cimetière', 'cimetiere', ['concession-cimetiere'], 'Concessions et règlement du cimetière.', 'arbre'),
])

# Pages hors menu (pied de page, utilitaires).
HORS_MENU = [
    ('contact', 'Contact', 'contact', ['contact'], {'pleine': True}),
    ('signalement', 'Signalement', 'signalement', ['x-signalement'], {'pleine': True, 'extrait': 'Un problème sur l’espace public ? Signalez-le à la mairie en quelques clics.', 'icone': 'signal'}),
    ('mentions-legales', 'Mentions légales', 'mentions-legales', ['mentions-legales'], {'pleine': True}),
    ('accessibilite-du-site', 'Accessibilité du site', 'accessibilite-du-site', ['x-accessibilite-du-site'], {'extrait': 'Adapter l’affichage à vos besoins et déclaration d’accessibilité du site villefontaine.fr.'}),
    ('plan-du-site', 'Plan du site', 'plan-du-site', ['plan-site'], {'pleine': True, 'extrait': 'Toutes les pages du site, rubrique par rubrique.'}),
    ('donnees-personnelles', 'Données personnelles', 'donnees-personnelles', ['politique-confidentialite'], {'pleine': True}),
    ('charte-graphique', 'Charte graphique', 'charte-graphique', ['charte-graphique'], {'pleine': True}),
    ('decouvrir', 'Découvrir Villefontaine', 'decouvrir-villefontaine', ['presentation-ville'], {}),
]

# Anciennes pages « carrefour » sans contenu propre : redirigées vers la rubrique la plus proche.
REDIRECTIONS_SUPPLEMENTAIRES = {
    # Anciennes adresses citées dans les contenus (relevées par la relecture du 18/11/2026).
    'seniors/phare': 'le-phare', 'dynamisme-economique-et-culturel/the-village': 'the-village', 'place-du-centre-ville': 'coeur-de-ville',
    'contacter-services-municipaux': 'services-municipaux', 'participer/conseil-municipal-enfants': 'cme',
    'logement-habitat/trouver-un-logement': 'logement-social', 'sante-et-solidarite/action-sociale': 'ccas',
    'operation-tranquillite-vacances': 'police-gendarmerie', 'dynamisme-economique-et-culturel/operations-grand-projet-coeur-de-ville': 'coeur-de-ville',
    'histoire-et-patrimoine-architectural/sites-remarquables': 'patrimoine', 'histoire-et-patrimoine-architectural': 'patrimoine',
    'participer/election': 'demarches-identite-citoyennete', 'dynamisme-economique-et-culturel/ville-poesie': 'culture',
    'habitant': 'demarches', 'professionnel': 'demarches-professionnels', 'association': 'associations', 'recherche-emploi': 'recrutement',
    'vie-municipale': 'la-mairie', 'participer': 'conseil-municipal', 'institutions-partenaires': 'jumelage', 'partenariats-jumelages': 'jumelage',
    'famille-enfance': 'enfance-famille', 'jeunesse-villarde': 'jeunesse', 'maison-pour-tous': 'maisons-pour-tous', 'seniors': 'seniors',
    'deplacements-transport': 'mobilite', 'sante-et-solidarite': 'sante-solidarite', 'emploi-et-insertion': 'emploi', 'services-et-commerces': 'commerces',
    'ville-attractive': 'commerces', 'tranquillite-publique': 'tranquillite', 'logement-habitat': 'logement', 'urbanisme': 'urbanisme',
    'voirie-proprete-dechets': 'proprete', 'accessibilite': 'accessibilite-ville', 'grands-projets-de-mandat': 'grands-projets', 'centre-ville': 'coeur-de-ville',
    'ville-innovante': 'campus', 'patrimoine-naturel-environnement': 'patrimoine', 'tourisme-patrimoine': 'patrimoine', 'culture': 'culture', 'art': 'street-art',
    'sport': 'sports', 'les-grands-rdv': 'grands-rdv', 'associations-villardes': 'associations', 'la-mairie': 'la-mairie', 'maison-du-pouvoir-achat': 'maison-pouvoir-achat',
    'ville-de-villefontaine': 'decouvrir', 'ville-de-villefontaine-new': 'decouvrir', 'enseignement-et-formation': 'enfance-famille',
    'publications': '/kiosque/', 'le-magazine-municipal-villefontaine-le-mag': '/kiosque/', 'agenda': '/agenda/', 'actualites': '/actualites/', 'carte-villefontaine': '/carte-des-services/',
    'flashs-infos': '/actualites/', 'rdv-emploi': 'structures-emploi', 'scot': 'plu', 'zones-activite-economique': 'zones-activite', 'grands-projets': 'grands-projets',
}
