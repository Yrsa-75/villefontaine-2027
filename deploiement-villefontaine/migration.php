<?php
/**
 * Migration des pages du site actuel vers le nouveau site.
 *
 * Le lot de pages (dist/migration/pages.json du dépôt) est préparé hors du site :
 * contenus convertis en blocs, arborescence, menu, redirections. Ici, on l'applique :
 * - création ou mise à jour de chaque page (reconnue par sa clé de migration : relancer ne crée pas de doublon) ;
 * - copie des images et documents de villefontaine.fr dans la médiathèque, puis mise à jour des adresses ;
 * - menu principal raccordé aux vraies pages, redirections 301 des anciennes adresses.
 * Le travail est découpé en tranches d'environ 40 secondes (le navigateur relance jusqu'à la fin).
 *
 * @package DeploiementVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lit le lot de migration du dernier état du dépôt (mis en cache 10 minutes).
 *
 * @return array|WP_Error
 */
function vf_deploi_lot_migration() {
	$m = vf_deploi_manifeste();
	if ( is_wp_error( $m ) ) {
		return $m;
	}
	$cle = 'vf_deploi_lot_' . substr( $m['commit'], 0, 12 );
	$lot = get_transient( $cle );
	if ( is_array( $lot ) ) {
		return $lot;
	}
	$rep = wp_remote_get( 'https://raw.githubusercontent.com/' . VF_DEPLOI_DEPOT . '/' . $m['commit'] . '/dist/migration/pages.json', array( 'timeout' => 30 ) );
	if ( is_wp_error( $rep ) || 200 !== wp_remote_retrieve_response_code( $rep ) ) {
		return new WP_Error( 'lot', 'Lot de migration introuvable dans le dépôt.' );
	}
	$lot = json_decode( wp_remote_retrieve_body( $rep ), true );
	if ( ! is_array( $lot ) || empty( $lot['pages'] ) ) {
		return new WP_Error( 'lot', 'Lot de migration illisible.' );
	}
	set_transient( $cle, $lot, 10 * MINUTE_IN_SECONDS );
	return $lot;
}

/**
 * Page déjà migrée pour une clé donnée.
 *
 * @param string $cle Clé de migration.
 * @return int
 */
function vf_deploi_page_par_cle( $cle ) {
	$ids = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'vf_cle_migration', // phpcs:ignore WordPress.DB.SlowDBQuery -- migration ponctuelle.
			'meta_value'     => $cle, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Copie dans la médiathèque les fichiers de villefontaine.fr cités dans un contenu et met les adresses à jour.
 *
 * @param string $contenu Contenu en blocs.
 * @param array  $erreurs Erreurs (modifié).
 * @return string
 */
function vf_deploi_medias_contenu( $contenu, array &$erreurs ) {
	if ( ! preg_match_all( '#https://villefontaine\.fr/wp-content/uploads/[^"\'\s<>)\\\\]+#', $contenu, $m ) ) {
		return $contenu;
	}
	foreach ( array_unique( $m[0] ) as $url ) {
		$res = vf_deploi_importer_image( $url );
		if ( is_wp_error( $res ) ) {
			$erreurs[] = basename( $url ) . ' : ' . $res->get_error_message();
			continue;
		}
		$nouvelle = wp_get_attachment_url( $res['id'] );
		$jeton    = 'VFIMG' . substr( md5( $url ), 0, 10 );
		$contenu  = str_replace( array( '"id":' . $jeton, 'wp-image-' . $jeton ), array( '"id":' . $res['id'], 'wp-image-' . $res['id'] ), $contenu );
		if ( $nouvelle ) {
			// Les images affichées en « large » pointent vers la taille large (plus légère que l'original).
			$contenu = str_replace( $url, $nouvelle, $contenu );
		}
	}
	// Jetons d'images restés sans fichier (image introuvable) : on retire l'identifiant pour garder un bloc valide.
	$contenu = preg_replace( '/"id":VFIMG[0-9a-f]{10},?/', '', $contenu );
	$contenu = preg_replace( '/ ?class="wp-image-VFIMG[0-9a-f]{10}"/', '', $contenu );
	return $contenu;
}

/**
 * Crée ou met à jour les pages du lot, à partir de la position $debut, pendant 40 secondes au plus.
 *
 * @param int $debut Position de départ.
 * @param string $seule Clé d'une seule page à traiter (vide = tout le lot).
 * @return array
 */
function vf_deploi_migrer_pages( $debut, $seule = '' ) {
	$lot = vf_deploi_lot_migration();
	if ( is_wp_error( $lot ) ) {
		return array( 'erreur' => $lot->get_error_message() );
	}
	require_once ABSPATH . 'wp-admin/includes/post.php';
	$depart  = microtime( true );
	$pages   = $lot['pages'];
	$total   = count( $pages );
	$faites  = array();
	$erreurs = array();
	$i       = $debut;
	for ( ; $i < $total; $i++ ) {
		if ( microtime( true ) - $depart > 40 ) {
			break;
		}
		$p        = $pages[ $i ];
		$cle      = sanitize_key( $p['cle'] );
		// Une seule page demandée (création ou mise à jour ciblée) : les autres ne sont pas touchées.
		if ( '' !== $seule && $cle !== $seule ) {
			continue;
		}
		$id       = vf_deploi_page_par_cle( $cle );
		$parent   = ! empty( $p['parent'] ) ? vf_deploi_page_par_cle( sanitize_key( $p['parent'] ) ) : 0;
		$contenu  = vf_deploi_medias_contenu( (string) $p['contenu'], $erreurs );
		$donnees  = array(
			'post_type'    => 'page',
			'post_status'  => in_array( $p['statut'] ?? 'publish', array( 'publish', 'draft' ), true ) ? $p['statut'] : 'publish',
			'post_title'   => sanitize_text_field( $p['titre'] ),
			'post_name'    => sanitize_title( $p['slug'] ),
			'post_parent'  => $parent,
			'menu_order'   => (int) ( $p['ordre'] ?? 0 ),
			'post_excerpt' => sanitize_textarea_field( $p['extrait'] ?? '' ),
			'post_content' => $contenu,
		);
		if ( $id ) {
			$donnees['ID'] = $id;
		}
		$res = wp_insert_post( wp_slash( $donnees ), true );
		if ( is_wp_error( $res ) ) {
			$erreurs[] = $p['titre'] . ' : ' . $res->get_error_message();
			continue;
		}
		update_post_meta( $res, 'vf_cle_migration', $cle );
		update_post_meta( $res, '_wp_page_template', ! empty( $p['gabarit'] ) ? sanitize_key( $p['gabarit'] ) : 'default' );
		// Encart contact de la colonne de droite.
		$contact = (array) ( $p['contact'] ?? array() );
		foreach ( array( 'titre', 'adresse', 'tel', 'courriel', 'lien', 'horaires' ) as $champ ) {
			update_post_meta( $res, 'vf_contact_' . $champ, isset( $contact[ $champ ] ) ? sanitize_textarea_field( $contact[ $champ ] ) : '' );
		}
		update_post_meta( $res, 'vf_contact_pmr', ! empty( $contact['pmr'] ) );
		update_post_meta( $res, 'vf_icone', sanitize_key( $p['icone'] ?? '' ) );
		// Image mise en avant.
		if ( ! empty( $p['image'] ) ) {
			$im = vf_deploi_importer_image( $p['image'] );
			if ( ! is_wp_error( $im ) ) {
				set_post_thumbnail( $res, $im['id'] );
			} else {
				$erreurs[] = $p['titre'] . ' (image) : ' . $im->get_error_message();
			}
		}
		$faites[] = $p['titre'];
	}
	return array(
		'faites'   => $faites,
		'suivant'  => $i < $total ? $i : null,
		'total'    => $total,
		'erreurs'  => $erreurs,
	);
}

/**
 * Finalisation : menu principal raccordé aux pages, menu « Vos démarches » mis à jour, redirections, nettoyage.
 *
 * @return array
 */
function vf_deploi_finaliser_migration() {
	$lot = vf_deploi_lot_migration();
	if ( is_wp_error( $lot ) ) {
		return array( 'erreur' => $lot->get_error_message() );
	}
	$bilan = array();

	// 1. Redirections : ancien chemin => ID de page (ou adresse).
	$table = array();
	foreach ( (array) ( $lot['redirections'] ?? array() ) as $ancien => $cible ) {
		$id = vf_deploi_page_par_cle( sanitize_key( $cible ) );
		$ch = function_exists( 'vf_socle_chemin_normalise' ) ? vf_socle_chemin_normalise( $ancien ) : $ancien;
		if ( $id ) {
			$table[ $ch ] = $id;
		} elseif ( 0 === strpos( (string) $cible, 'http' ) || 0 === strpos( (string) $cible, '/' ) ) {
			$table[ $ch ] = esc_url_raw( $cible );
		}
	}
	update_option( 'vf_redirections', $table, false );
	$bilan[] = count( $table ) . ' redirections enregistrées.';

	// 2. Menu principal : on remplace les éléments du menu affecté à l'emplacement.
	$emplacements = get_nav_menu_locations();
	$menu_id      = $emplacements['vf-principal'] ?? 0;
	if ( ! $menu_id || ! wp_get_nav_menu_object( $menu_id ) ) {
		$menu_id                        = wp_create_nav_menu( 'Méga-menu principal' );
		$emplacements['vf-principal'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $emplacements );
	}
	foreach ( (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) ) as $el ) {
		wp_delete_post( $el->ID, true );
	}
	$ajoute = function ( $item, $parent ) use ( &$ajoute, $menu_id ) {
		$id   = ! empty( $item['cle'] ) ? vf_deploi_page_par_cle( sanitize_key( $item['cle'] ) ) : 0;
		$args = array(
			'menu-item-title'       => sanitize_text_field( $item['titre'] ),
			'menu-item-status'      => 'publish',
			'menu-item-parent-id'   => $parent,
			'menu-item-description' => sanitize_text_field( $item['description'] ?? '' ),
		);
		if ( $id ) {
			$args += array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $id );
		} else {
			$args += array( 'menu-item-type' => 'custom', 'menu-item-url' => esc_url_raw( $item['url'] ?? '#' ) );
		}
		$nouveau = wp_update_nav_menu_item( $menu_id, 0, $args );
		foreach ( (array) ( $item['enfants'] ?? array() ) as $enfant ) {
			$ajoute( $enfant, is_wp_error( $nouveau ) ? 0 : $nouveau );
		}
	};
	foreach ( (array) ( $lot['menu'] ?? array() ) as $rubrique ) {
		$ajoute( $rubrique, 0 );
	}
	$bilan[] = 'Menu principal reconstruit.';

	// 3. Menu « Vos démarches » : les liens vers les anciennes pages pointent vers les nouvelles.
	$dem = $emplacements['vf-demarches'] ?? 0;
	$n   = 0;
	if ( $dem ) {
		foreach ( (array) wp_get_nav_menu_items( $dem ) as $el ) {
			if ( 'custom' !== $el->type || false === strpos( $el->url, '://villefontaine.fr/' ) ) {
				continue;
			}
			$ch = function_exists( 'vf_socle_chemin_normalise' ) ? vf_socle_chemin_normalise( $el->url ) : '';
			if ( isset( $table[ $ch ] ) ) {
				$url = is_numeric( $table[ $ch ] ) ? get_permalink( (int) $table[ $ch ] ) : $table[ $ch ];
				$frag = (string) wp_parse_url( $el->url, PHP_URL_FRAGMENT );
				update_post_meta( $el->ID, '_menu_item_url', esc_url_raw( $url . ( $frag ? '#' . $frag : '' ) ) );
				++$n;
			}
		}
	}
	$bilan[] = $n . ' liens du panneau « Vos démarches » mis à jour.';

	flush_rewrite_rules();
	return array( 'bilan' => $bilan );
}

/**
 * Lit le lot des actualités et événements (dist/migration/contenus.json), mis en cache 10 minutes.
 *
 * @return array|WP_Error
 */
function vf_deploi_lot_contenus() {
	$m = vf_deploi_manifeste();
	if ( is_wp_error( $m ) ) {
		return $m;
	}
	$cle = 'vf_deploi_contenus_' . substr( $m['commit'], 0, 12 );
	$lot = get_transient( $cle );
	if ( is_array( $lot ) ) {
		return $lot;
	}
	$rep = wp_remote_get( 'https://raw.githubusercontent.com/' . VF_DEPLOI_DEPOT . '/' . $m['commit'] . '/dist/migration/contenus.json', array( 'timeout' => 30 ) );
	if ( is_wp_error( $rep ) || 200 !== wp_remote_retrieve_response_code( $rep ) ) {
		return new WP_Error( 'lot', 'Lot des actualités et événements introuvable dans le dépôt.' );
	}
	$lot = json_decode( wp_remote_retrieve_body( $rep ), true );
	if ( ! is_array( $lot ) ) {
		return new WP_Error( 'lot', 'Lot des actualités et événements illisible.' );
	}
	set_transient( $cle, $lot, 10 * MINUTE_IN_SECONDS );
	return $lot;
}

/**
 * Contenu déjà migré (clé de migration), ou à défaut contenu existant de même adresse.
 *
 * @param string $type Type de contenu.
 * @param string $cle  Clé de migration.
 * @param string $slug Identifiant d'adresse d'origine.
 * @return int
 */
function vf_deploi_contenu_existant( $type, $cle, $slug ) {
	$ids = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'vf_cle_migration', // phpcs:ignore WordPress.DB.SlowDBQuery -- migration ponctuelle.
			'meta_value'     => $cle, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $ids ) {
		return (int) $ids[0];
	}
	// Actualités déjà reprises à la main (page d'accueil) : on les retrouve par leur adresse.
	if ( 'post' === $type && $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'post' );
		if ( $p ) {
			return (int) $p->ID;
		}
	}
	return 0;
}

/**
 * Identifiant d'un terme (créé s'il n'existe pas).
 *
 * @param string $nom      Nom du terme.
 * @param string $taxonomie Taxonomie.
 * @return int
 */
function vf_deploi_terme( $nom, $taxonomie ) {
	$nom = sanitize_text_field( $nom );
	if ( '' === $nom || ! taxonomy_exists( $taxonomie ) ) {
		return 0;
	}
	$t = term_exists( $nom, $taxonomie );
	if ( ! $t ) {
		$t = wp_insert_term( $nom, $taxonomie );
	}
	return is_wp_error( $t ) ? 0 : (int) $t['term_id'];
}

/**
 * Crée ou met à jour les actualités ou les événements du lot, par tranches de 40 secondes.
 * Les anciennes adresses sont ajoutées à la table des redirections.
 *
 * @param string $etape « actualites » ou « evenements ».
 * @param int    $debut Position de départ.
 * @return array
 */
function vf_deploi_migrer_contenus( $etape, $debut ) {
	$lot = vf_deploi_lot_contenus();
	if ( is_wp_error( $lot ) ) {
		return array( 'erreur' => $lot->get_error_message() );
	}
	$evt   = 'evenements' === $etape;
	$type  = $evt ? 'vf_evenement' : 'post';
	$liste = (array) ( $lot[ $etape ] ?? array() );
	if ( $evt && ! post_type_exists( 'vf_evenement' ) ) {
		return array( 'erreur' => 'Le plugin Agenda doit être actif.' );
	}
	$depart  = microtime( true );
	$total   = count( $liste );
	$faites  = array();
	$erreurs = array();
	$redir   = (array) get_option( 'vf_redirections', array() );
	$i       = $debut;
	for ( ; $i < $total; $i++ ) {
		if ( microtime( true ) - $depart > 40 ) {
			break;
		}
		$c       = $liste[ $i ];
		$cle     = sanitize_key( $c['cle'] );
		$id      = vf_deploi_contenu_existant( $type, $cle, sanitize_title( $c['slug'] ) );
		$contenu = vf_deploi_medias_contenu( (string) $c['contenu'], $erreurs );
		$donnees = array(
			'post_type'     => $type,
			'post_status'   => 'publish',
			'post_title'    => sanitize_text_field( $c['titre'] ),
			'post_name'     => sanitize_title( $c['slug'] ), // Même adresse que sur l'ancien site.
			'post_content'  => $contenu,
			'post_excerpt'  => sanitize_textarea_field( $c['extrait'] ?? '' ),
			'post_date'     => sanitize_text_field( $c['date'] ),
			'post_date_gmt' => get_gmt_from_date( sanitize_text_field( $c['date'] ) ),
		);
		if ( $id ) {
			$donnees['ID'] = $id;
			unset( $donnees['post_name'] );
		}
		$res = wp_insert_post( wp_slash( $donnees ), true );
		if ( is_wp_error( $res ) ) {
			$erreurs[] = $c['titre'] . ' : ' . $res->get_error_message();
			continue;
		}
		update_post_meta( $res, 'vf_cle_migration', $cle );
		if ( $evt ) {
			$meta = array(
				'vf_debut'       => $c['debut'],
				'vf_fin'         => $c['fin'],
				'vf_heure_debut' => $c['heure_debut'],
				'vf_heure_fin'   => $c['heure_fin'],
				'vf_lieu'        => $c['lieu'],
				'vf_infos'       => $c['infos'],
			);
			foreach ( $meta as $k => $v ) {
				update_post_meta( $res, $k, sanitize_text_field( (string) $v ) );
			}
			update_post_meta( $res, 'vf_lien', esc_url_raw( (string) $c['lien'] ) );
			$themes = array_filter( array_map( static function ( $n ) { return vf_deploi_terme( $n, 'vf_theme_evenement' ); }, (array) $c['themes'] ) );
			wp_set_object_terms( $res, array_map( 'intval', $themes ), 'vf_theme_evenement' );
			if ( function_exists( 'vf_agenda_calcule_fin' ) ) {
				vf_agenda_calcule_fin( $res );
			}
		} else {
			$cats = array_filter( array_map( static function ( $n ) { return vf_deploi_terme( $n, 'category' ); }, (array) $c['categories'] ) );
			if ( $cats ) {
				wp_set_post_categories( $res, array_map( 'intval', $cats ) );
			}
		}
		if ( ! empty( $c['image'] ) ) {
			$im = vf_deploi_importer_image( $c['image'] );
			if ( ! is_wp_error( $im ) ) {
				set_post_thumbnail( $res, $im['id'] );
			} else {
				$erreurs[] = $c['titre'] . ' (image) : ' . $im->get_error_message();
			}
		}
		if ( ! empty( $c['ancien'] ) && function_exists( 'vf_socle_chemin_normalise' ) ) {
			$redir[ vf_socle_chemin_normalise( $c['ancien'] ) ] = $res;
		}
		$faites[] = $c['titre'];
	}
	update_option( 'vf_redirections', $redir, false );
	return array(
		'faites'  => $faites,
		'suivant' => $i < $total ? $i : null,
		'total'   => $total,
		'erreurs' => $erreurs,
	);
}
