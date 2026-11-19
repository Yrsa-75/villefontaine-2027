<?php
/**
 * Recherche prédictive : index léger de tout le site (démarches, pages, actualités, événements à venir,
 * lieux), servi par la route REST vf-socle/v1/index et filtré dans le navigateur à chaque frappe.
 *
 * L'index est calculé une fois puis gardé en cache ; il est recalculé dès qu'un contenu ou un menu change.
 * Aucune donnée personnelle, uniquement des contenus publiés.
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Construit l'index (tableau de lignes compactes).
 * Clés : t = type, l = libellé, u = adresse, c = complément affiché, m = mots-clés supplémentaires.
 *
 * @return array
 */
function vf_socle_index_recherche() {
	$cache = get_transient( 'vf_socle_index_recherche' );
	if ( is_array( $cache ) ) {
		return $cache;
	}
	$index = array();
	$court = static function ( $texte, $mots = 30 ) {
		return wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) $texte ) ), $mots, '' );
	};

	// 1. Démarches : éléments du menu « Vos démarches » (avec le nom de leur rubrique).
	foreach ( vf_socle_arbre_menu( 'vf-demarches' ) as $profil ) {
		foreach ( $profil['enfants'] as $section ) {
			$feuilles = $section['enfants'] ? $section['enfants'] : array( $section );
			foreach ( $feuilles as $d ) {
				if ( empty( $d['url'] ) || '#' === $d['url'] ) {
					continue;
				}
				$index[] = array(
					't' => 'demarche',
					'l' => wp_strip_all_tags( $d['titre'] ),
					'u' => $d['url'],
					'c' => wp_strip_all_tags( $section['titre'] ),
				);
			}
		}
	}

	// 2. Pages.
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 400,
			'no_found_rows'  => true,
			'exclude'        => array_filter( array( (int) get_option( 'page_on_front' ) ) ),
		)
	);
	foreach ( $pages as $p ) {
		$parent  = $p->post_parent ? get_the_title( $p->post_parent ) : '';
		$index[] = array(
			't' => 'page',
			'l' => html_entity_decode( get_the_title( $p ), ENT_QUOTES ),
			'u' => get_permalink( $p ),
			'c' => html_entity_decode( $parent, ENT_QUOTES ),
			'm' => $court( $p->post_excerpt ? $p->post_excerpt : $p->post_content, 25 ),
		);
	}

	// 3. Actualités (les 60 plus récentes).
	foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 60, 'no_found_rows' => true ) ) as $p ) {
		$index[] = array(
			't' => 'actualite',
			'l' => html_entity_decode( get_the_title( $p ), ENT_QUOTES ),
			'u' => get_permalink( $p ),
			'c' => wp_date( 'j F Y', get_post_timestamp( $p ) ),
			'm' => $court( $p->post_excerpt ? $p->post_excerpt : $p->post_content, 20 ),
		);
	}

	// 4. Événements à venir (fin non dépassée), les plus proches d'abord.
	if ( post_type_exists( 'vf_evenement' ) ) {
		$evts = get_posts(
			array(
				'post_type'      => 'vf_evenement',
				'post_status'    => 'publish',
				'posts_per_page' => 150,
				'no_found_rows'  => true,
				'meta_key'       => 'vf_debut', // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array( array( 'key' => 'vf_fin_effective', 'value' => wp_date( 'Y-m-d' ), 'compare' => '>=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$vus = array();
		foreach ( $evts as $p ) {
			$titre = html_entity_decode( get_the_title( $p ), ENT_QUOTES );
			if ( isset( $vus[ $titre ] ) ) {
				continue; // Rendez-vous répété : seule la prochaine date est proposée.
			}
			$vus[ $titre ] = true;
			$debut         = (string) get_post_meta( $p->ID, 'vf_debut', true );
			$index[]       = array(
				't' => 'evenement',
				'l' => $titre,
				'u' => get_permalink( $p ),
				'c' => $debut ? wp_date( 'j F Y', strtotime( $debut . ' 12:00' ) ) : '',
				'm' => (string) get_post_meta( $p->ID, 'vf_lieu', true ),
			);
		}
	}

	// 5. Lieux de la carte des services.
	if ( post_type_exists( 'vf_lieu' ) ) {
		foreach ( get_posts( array( 'post_type' => 'vf_lieu', 'post_status' => 'publish', 'posts_per_page' => 500, 'no_found_rows' => true ) ) as $p ) {
			$cats    = get_the_terms( $p, 'vf_categorie_lieu' );
			$index[] = array(
				't' => 'lieu',
				'l' => html_entity_decode( get_the_title( $p ), ENT_QUOTES ),
				'u' => get_permalink( $p ),
				'c' => ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '',
				'm' => (string) get_post_meta( $p->ID, 'vf_adresse', true ),
			);
		}
	}

	set_transient( 'vf_socle_index_recherche', $index, DAY_IN_SECONDS );
	return $index;
}

/**
 * L'index est recalculé dès qu'un contenu publié ou un menu change.
 */
function vf_socle_index_recherche_perime() {
	delete_transient( 'vf_socle_index_recherche' );
}
add_action( 'save_post', 'vf_socle_index_recherche_perime' );
add_action( 'deleted_post', 'vf_socle_index_recherche_perime' );
add_action( 'wp_update_nav_menu', 'vf_socle_index_recherche_perime' );

/**
 * Route REST publique (contenus publiés uniquement), mise en cache 10 minutes par le navigateur.
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'vf-socle/v1',
			'/index',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					$rep = rest_ensure_response( vf_socle_index_recherche() );
					$rep->header( 'Cache-Control', 'public, max-age=600' );
					return $rep;
				},
			)
		);
	}
);

/**
 * Le script n'est chargé que là où il sert : accueil (grand visuel) et page de résultats.
 */
function vf_socle_script_suggestions() {
	if ( ! is_front_page() && ! is_search() ) {
		return;
	}
	wp_enqueue_script(
		'vf-socle-suggestions',
		VF_SOCLE_URL . 'assets/js/suggestions.js',
		array(),
		VF_SOCLE_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_localize_script( 'vf-socle-suggestions', 'vfSuggestions', array( 'index' => esc_url_raw( rest_url( 'vf-socle/v1/index' ) ) ) );
	wp_enqueue_style( 'vf-socle-suggestions', VF_SOCLE_URL . 'assets/css/suggestions.css', array(), VF_SOCLE_VERSION );
}
add_action( 'wp_enqueue_scripts', 'vf_socle_script_suggestions' );
