<?php
/**
 * Menus du site : emplacements, lecture en arborescence et menus de départ.
 *
 * Les agents gèrent le méga-menu et le panneau « Vos démarches » depuis
 * Apparence > Menus, sans toucher au code.
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Déclare les emplacements de menus. Cela fait aussi apparaître Apparence > Menus.
 */
function vf_socle_emplacements_menus() {
	register_nav_menus(
		array(
			'vf-principal' => 'Méga-menu principal (3 niveaux)',
			'vf-demarches' => 'Panneau « Vos démarches » (profil > rubrique > démarche)',
		)
	);
}
add_action( 'after_setup_theme', 'vf_socle_emplacements_menus', 20 );

/**
 * Lit le menu affecté à un emplacement et le renvoie en arborescence.
 *
 * Chaque élément : titre, url, description, image (id de l'image mise en avant de la page liée), enfants.
 *
 * @param string $emplacement Emplacement du menu.
 * @return array
 */
function vf_socle_arbre_menu( $emplacement ) {
	$emplacements = get_nav_menu_locations();
	if ( empty( $emplacements[ $emplacement ] ) ) {
		return array();
	}
	$elements = wp_get_nav_menu_items( (int) $emplacements[ $emplacement ], array( 'update_post_term_cache' => false ) );
	if ( ! $elements ) {
		return array();
	}
	// Regroupe les éléments par parent (WordPress les fournit déjà dans l'ordre du menu).
	$enfants_de = array();
	foreach ( $elements as $el ) {
		$enfants_de[ (int) $el->menu_item_parent ][] = array(
			'id'          => (int) $el->ID,
			'titre'       => $el->title,
			'url'         => $el->url,
			'description' => isset( $el->description ) ? trim( $el->description ) : '',
			'classes'     => array_values( array_filter( (array) $el->classes ) ),
			'image'       => ( 'post_type' === $el->type ) ? (int) get_post_thumbnail_id( (int) $el->object_id ) : 0,
			'courant'     => ( 'post_type' === $el->type && is_singular() && (int) $el->object_id === get_queried_object_id() ),
		);
	}
	$construit = function ( $parent, $profondeur ) use ( &$construit, $enfants_de ) {
		$branche = array();
		foreach ( $enfants_de[ $parent ] ?? array() as $el ) {
			$el['enfants'] = $profondeur < 4 ? $construit( $el['id'], $profondeur + 1 ) : array();
			$branche[]     = $el;
		}
		return $branche;
	};
	return $construit( 0, 1 );
}

/* ---------------------------------------------------------------------------
   Menus de départ (créés depuis Réglages > Villefontaine)
   --------------------------------------------------------------------------- */

/**
 * Arborescence du méga-menu de départ, issue de l'arborescence validée.
 * Les liens pointent vers « # » : ils seront raccordés aux vraies pages lors de la migration.
 *
 * @return array
 */
function vf_socle_menu_principal_depart() {
	return array(
		array( 'La mairie', 'Vos élus, le budget, les services municipaux et la vie démocratique.', array(
			array( 'Services municipaux', array( 'Annuaire des services', 'Contacts', 'Organigramme' ) ),
			array( 'Conseil municipal', array( 'Les élus', 'Nos engagements', 'Expression des groupes', 'Conseil municipal des enfants' ) ),
			array( 'Budget de la commune', array( 'Budgets 2022 à 2026', 'Chiffres clés' ) ),
			array( 'Budget participatif', array( 'Proposer un projet', 'Projets réalisés' ) ),
			array( 'Jumelage', array( 'Nos 4 villes jumelées' ) ),
			array( 'Marchés publics', array() ),
			array( 'Villefontaine recrute', array( 'Offres d’emploi', 'Candidature spontanée' ) ),
			array( 'Affichage légal', array( 'Délibérations', 'Arrêtés' ) ),
		) ),
		array( 'Vie quotidienne', 'École, jeunesse, seniors, santé, emploi : les services de tous les jours.', array(
			array( 'Enfance, famille', array( 'Scolarité', 'Restauration scolaire et loisirs', 'Petite enfance' ) ),
			array( 'Jeunesse', array( 'Info Jeunes', 'Maisons pour tous' ) ),
			array( 'Seniors', array( 'Le Phare', 'Le Voltaire', 'Plan canicule', 'Portage de repas' ) ),
			array( 'Mobilité et stationnement', array( 'Mobilités douces et plan vélo', 'Covoiturage', 'Transports en commun', 'Stationnement gratuit' ) ),
			array( 'Santé et solidarité', array( 'CCAS et action sociale', 'Maison du pouvoir d’achat', 'Complémentaire santé', 'Handicap' ) ),
			array( 'Économie, emploi et insertion', array( 'Mission locale', 'Relais emploi du CCAS', 'Chantier insertion' ) ),
			array( 'Commerces et marchés', array( 'S’implanter', 'Zones d’activité', 'Commerce de proximité et marchés', 'The Village' ) ),
			array( 'Services publics', array() ),
		) ),
		array( 'Cadre de vie', 'Sécurité, logement, urbanisme, propreté et grands projets de la ville.', array(
			array( 'Tranquillité et sécurité', array( 'Police municipale et gendarmerie', 'Ville vigilante', 'Maison de la justice et du droit' ) ),
			array( 'Logement et habitat', array( 'S’installer à Villefontaine', 'Infos ENEDIS et SEMIDAO' ) ),
			array( 'Urbanisme', array( 'PLU', 'Démarches d’urbanisme', 'Occupation du domaine public' ) ),
			array( 'Propreté et déchets', array( 'La collecte', 'Déchèteries' ) ),
			array( 'Accessibilité', array() ),
			array( 'Les grands projets', array() ),
			array( 'Patrimoine historique et naturel', array() ),
		) ),
		array( 'Sortir et bouger', 'Culture, sport, nature et associations : profitez de votre ville.', array(
			array( 'Vie culturelle', array( 'Médiathèque et bibliothèques', 'Théâtre et cinéma', 'Parcours street art' ) ),
			array( 'Sports et loisirs', array() ),
			array( 'Jardins et espaces verts', array() ),
			array( 'Les grands rendez-vous', array() ),
			array( 'Associations', array( 'Annuaire des associations', 'Demande de subvention' ) ),
		) ),
	);
}

/**
 * Démarches de départ, relevées sur le site actuel (menu « Démarches administratives »).
 * Structure : profil (classe d'icône) > rubrique (vide = sans titre) > démarche (titre, adresse).
 *
 * @return array
 */
function vf_socle_menu_demarches_depart() {
	$ec = 'https://villefontaine.espace-citoyen.fr/#!portreq';
	$vf = 'https://villefontaine.fr';
	return array(
		array( 'Habitant', 'vf-icone-maison', array(
			array( 'Papiers et citoyenneté', array(
				array( 'Demande d’un acte de naissance', $ec ),
				array( 'Demande d’un acte de mariage', $ec ),
				array( 'Se marier à Villefontaine', $ec ),
				array( 'Demande d’un acte de décès', $ec ),
				array( 'Demande de carte d’identité ou de passeport', $ec ),
				array( 'Changer son prénom', $ec ),
				array( 'Changement de nom', $ec ),
				array( 'Permis de conduire et carte grise', $vf . '/habitant/permis-conduire-carte-grise/' ),
				array( 'Demande d’inscription sur les listes électorales', $ec ),
				array( 'PACS', $ec ),
				array( 'Parrainage civil', $ec ),
				array( 'Recensement militaire', $ec ),
				array( 'Attestation d’accueil', $ec ),
				array( 'Demande de la Médaille d’honneur du travail', 'https://www.demarches-simplifiees.fr/commencer/mhtravail' ),
				array( 'Autorisation de sortie du territoire (AST)', 'https://www.service-public.fr/particuliers/vosdroits/F1359' ),
				array( 'Certificat de vie commune ou de concubinage', $ec ),
				array( 'Légalisation de signature', $ec ),
				array( 'Copie certifiée conforme', $ec ),
				array( 'Paiement de proximité', 'https://www.impots.gouv.fr/portail/paiement-de-proximite' ),
			) ),
			array( 'Services aux habitants', array(
				array( 'Stationnement pour un déménagement (particulier)', $vf . '/habitant/demande-dautorisation-de-stationnement-pour-un-demenagement-realise-par-un-particulier/' ),
				array( 'Les démarches d’urbanisme', $vf . '/habitat-et-urbanisme/demarches-urbanisme/' ),
				array( 'Portail Famille', 'https://villefontaine.portailfamille.net/' ),
				array( 'Portage de repas à domicile', $vf . '/portage-de-repas-a-domicile/' ),
				array( 'Inscription télé-alarme', $vf . '/inscription-tele-alarme/' ),
				array( 'Demande de logement social', $vf . '/habitant/demande-de-logement-social/' ),
				array( 'Opération tranquillité vacances', $vf . '/habitant/operation-tranquillite-vacances/' ),
				array( 'Inscription voisins vigilants', $vf . '/habitant/inscription-voisins-vigilants/' ),
				array( 'Organiser un vide-maison', $vf . '/organiser-vide-maison/' ),
				array( 'Concession cimetière', $vf . '/concession-cimetiere/' ),
				array( 'Déclaration de chien dangereux', $vf . '/habitant/declaration-de-chien-dangereux/' ),
			) ),
			array( 'Réservation de salle', array(
				array( 'Réserver une salle', $ec ),
			) ),
		) ),
		array( 'Professionnel', 'vf-icone-idee', array(
			array( '', array(
				array( 'Faire connaître votre activité', $vf . '/entreprise/faire-connaitre-votre-activite/' ),
				array( 'Stationnement pour un déménagement (entreprise)', $vf . '/entreprise/demande-dautorisation-de-stationnement-pour-un-demenagement-realise-par-une-entreprise/' ),
				array( 'Occupation du domaine public pour chantier privé', $vf . '/entreprise/demande-occupation-domaine-public-pour-chantier-prive/' ),
				array( 'Déclaration préalable d’une vente au déballage', $vf . '/entreprise/declaration-vente-deballage/' ),
				array( 'Demande de terrasse et étalage', $vf . '/demande-terrasse-etalage/' ),
				array( 'Aide au développement des petites entreprises (commerce, artisanat, services)', $vf . '/demande-aide-developpement-petites-entreprises/' ),
				array( 'Autorisation de stationnement de taxi (licence de taxi)', $vf . '/demande-autorisation-stationnement-taxi/' ),
				array( 'Demande de registre de police', $vf . '/demande-registre-police/' ),
				array( 'Déclaration de débit de boissons permanent', $vf . '/demande-debits-boissons-permanent/' ),
				array( 'Emplacement pour un food-truck (hors marchés)', $vf . '/demande-emplacement-food-truck-hors-marches/' ),
				array( 'Emplacement sur les marchés de plein air', $vf . '/demande-emplacement-marche-plein-air/' ),
				array( 'Accès à l’espace piéton du centre-ville', $vf . '/demande-acces-pieton-centre-ville/' ),
			) ),
		) ),
		array( 'Association', 'vf-icone-asso', array(
			array( 'Vie associative', array(
				array( 'Demande de subvention', $vf . '/association/demande-de-subvention/' ),
				array( 'Promouvoir votre événement', $vf . '/association/organiser-une-manifestation/' ),
				array( 'Organiser un vide-grenier', $vf . '/organiser-vide-grenier/' ),
			) ),
			array( 'Réserver une salle', array(
				array( 'Espace France Gall', $ec ),
				array( 'Salle Didier Latimier', $ec ),
				array( 'Salle Henri Bonnet', $ec ),
				array( 'Salle Pierre Coignard', $ec ),
				array( 'Salle Sadi Desquesnes', $ec ),
				array( 'Salle de spectacle Daniel Balavoine', $vf . '/reserver-salle-daniel-balavoine/' ),
			) ),
		) ),
		array( 'Recherche d’emploi', 'vf-icone-citoyen', array(
			array( '', array(
				array( 'Le recrutement de la ville', $vf . '/emploi-et-insertion/recrutement-ville/' ),
				array( 'Candidature spontanée', $vf . '/recherche-emploi/candidature-spontanee/' ),
				array( 'Candidature à un stage ou contrat d’apprentissage', 'https://villefontaine-recrutement.simply-jobs.fr/cv/upload/vacancy/346' ),
			) ),
		) ),
	);
}

/**
 * Ajoute un lien personnalisé à un menu.
 *
 * @param int    $menu_id  Menu.
 * @param string $titre    Libellé.
 * @param string $url      Adresse.
 * @param int    $parent   Élément parent (0 = racine).
 * @param array  $extra    Description et classes éventuelles.
 * @return int Identifiant de l'élément créé.
 */
function vf_socle_ajoute_lien_menu( $menu_id, $titre, $url, $parent = 0, $extra = array() ) {
	$id = wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'       => $titre,
			'menu-item-url'         => $url,
			'menu-item-status'      => 'publish',
			'menu-item-type'        => 'custom',
			'menu-item-parent-id'   => $parent,
			'menu-item-description' => $extra['description'] ?? '',
			'menu-item-classes'     => $extra['classes'] ?? '',
		)
	);
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Crée un menu de départ et l'affecte à son emplacement. Ne fait rien si l'emplacement a déjà un menu.
 *
 * @param string $emplacement « vf-principal » ou « vf-demarches ».
 * @return string|WP_Error Message de résultat.
 */
function vf_socle_cree_menu_depart( $emplacement ) {
	$emplacements = get_nav_menu_locations();
	if ( ! empty( $emplacements[ $emplacement ] ) && wp_get_nav_menu_object( $emplacements[ $emplacement ] ) ) {
		return new WP_Error( 'deja', 'Un menu est déjà affecté à cet emplacement : rien n’a été modifié.' );
	}
	$nom     = ( 'vf-principal' === $emplacement ) ? 'Méga-menu principal' : 'Vos démarches';
	$menu_id = wp_create_nav_menu( $nom . ' (' . wp_date( 'd/m/Y' ) . ')' );
	if ( is_wp_error( $menu_id ) ) {
		return $menu_id;
	}

	if ( 'vf-principal' === $emplacement ) {
		foreach ( vf_socle_menu_principal_depart() as $rubrique ) {
			$id1 = vf_socle_ajoute_lien_menu( $menu_id, $rubrique[0], '#', 0, array( 'description' => $rubrique[1] ) );
			foreach ( $rubrique[2] as $rang2 ) {
				$id2 = vf_socle_ajoute_lien_menu( $menu_id, $rang2[0], '#', $id1 );
				foreach ( $rang2[1] as $rang3 ) {
					vf_socle_ajoute_lien_menu( $menu_id, $rang3, '#', $id2 );
				}
			}
		}
	} else {
		foreach ( vf_socle_menu_demarches_depart() as $profil ) {
			$id1 = vf_socle_ajoute_lien_menu( $menu_id, $profil[0], '#', 0, array( 'classes' => $profil[1] ) );
			foreach ( $profil[2] as $rubrique ) {
				// Rubrique sans titre : les démarches sont rattachées directement au profil.
				$id2 = ( '' === $rubrique[0] ) ? $id1 : vf_socle_ajoute_lien_menu( $menu_id, $rubrique[0], '#', $id1 );
				foreach ( $rubrique[1] as $demarche ) {
					vf_socle_ajoute_lien_menu( $menu_id, $demarche[0], $demarche[1], $id2 );
				}
			}
		}
	}

	$emplacements[ $emplacement ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $emplacements );
	return 'Le menu « ' . $nom . ' » a été créé et affecté. Vous pouvez le modifier dans Apparence > Menus.';
}
