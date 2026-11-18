<?php
/**
 * Déclaration des blocs du Socle.
 *
 * Chaque bloc est un dossier de blocs/ avec :
 * - block.json : description du bloc (réglages, styles) ;
 * - rendu.php  : affichage sur le site (bloc « dynamique » : le HTML est produit à l'affichage,
 *                donc une évolution du design se répercute partout sans retoucher les pages) ;
 * - editeur.js : interface dans l'éditeur ;
 * - style.css  : chargé UNIQUEMENT sur les pages qui contiennent le bloc ;
 * - vue.js     : comportement côté visiteur (s'il y en a un), chargé en différé.
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Liste des blocs du Socle (nom du dossier).
 *
 * @return string[]
 */
function vf_socle_liste_blocs() {
	return array( 'entete', 'panneau-demarches', 'heros', 'acces-rapides', 'section', 'bandeau-page', 'fil-ariane', 'sommaire', 'encart-contact', 'sous-pages', 'documents', 'chiffres', 'cartes-liens', 'contact-carte', 'video', 'trombinoscope', 'formulaire-contact' );
}

/**
 * Enregistre les scripts puis les blocs.
 */
function vf_socle_enregistre_blocs() {
	$deps_editeur = array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data', 'wp-core-data' );

	// Petite boîte à outils partagée par les interfaces des blocs dans l'éditeur.
	wp_register_script( 'vf-socle-editeur', VF_SOCLE_URL . 'assets/js/editeur-commun.js', $deps_editeur, VF_SOCLE_VERSION, true );
	wp_localize_script(
		'vf-socle-editeur',
		'vfSocleDonnees',
		array( 'icones' => vf_socle_icones_libelles() )
	);

	foreach ( vf_socle_liste_blocs() as $nom ) {
		$dossier = VF_SOCLE_DIR . 'blocs/' . $nom;
		$url     = VF_SOCLE_URL . 'blocs/' . $nom;

		if ( file_exists( $dossier . '/editeur.js' ) ) {
			wp_register_script( 'vf-socle-' . $nom . '-editeur', $url . '/editeur.js', array( 'vf-socle-editeur' ), VF_SOCLE_VERSION, true );
		}
		if ( file_exists( $dossier . '/vue.js' ) ) {
			wp_register_script(
				'vf-socle-' . $nom . '-vue',
				$url . '/vue.js',
				array(),
				VF_SOCLE_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
		register_block_type( $dossier );
	}
}
add_action( 'init', 'vf_socle_enregistre_blocs' );

/**
 * Catégorie « Villefontaine » dans la liste des blocs de l'éditeur.
 *
 * @param array $categories Catégories existantes.
 * @return array
 */
function vf_socle_categorie_blocs( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'villefontaine',
			'title' => 'Villefontaine',
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'vf_socle_categorie_blocs' );

/**
 * Récupère l'image d'un bloc : média choisi, sinon image par défaut fournie par le thème.
 *
 * @param int    $id      Identifiant du média.
 * @param string $taille  Taille WordPress.
 * @param array  $attrs   Attributs HTML.
 * @return string
 */
function vf_socle_image( $id, $taille, $attrs = array() ) {
	$attrs = wp_parse_args( $attrs, array( 'alt' => '' ) );
	if ( $id && wp_attachment_is_image( $id ) ) {
		return wp_get_attachment_image( $id, $taille, false, $attrs );
	}
	return '';
}

/**
 * Éléments du fil d'Ariane de la page en cours : [ [titre, url|null], ... ].
 *
 * @param int $post_id Contenu concerné (0 = page affichée).
 * @return array
 */
function vf_socle_elements_ariane( $post_id = 0 ) {
	$elements = array( array( 'Accueil', home_url( '/' ) ) );
	if ( $post_id || is_singular() ) {
		$post = get_post( $post_id ? $post_id : get_queried_object_id() );
		if ( ! $post ) {
			return $elements;
		}
		if ( 'page' === $post->post_type ) {
			foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancetre ) {
				$elements[] = array( get_the_title( $ancetre ), get_permalink( $ancetre ) );
			}
		} elseif ( 'post' === $post->post_type ) {
			$page_actus = (int) get_option( 'page_for_posts' );
			if ( $page_actus ) {
				$elements[] = array( get_the_title( $page_actus ), get_permalink( $page_actus ) );
			}
			$cats = get_the_category( $post->ID );
			// La catégorie n'est affichée que si elle apporte une information (pas « Actualités » sous « Actualités »).
			if ( $cats && ( ! $page_actus || 0 !== strcasecmp( $cats[0]->name, get_the_title( $page_actus ) ) ) && 'uncategorized' !== $cats[0]->slug ) {
				$elements[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
			}
		} else {
			$type = get_post_type_object( $post->post_type );
			if ( $type && $type->has_archive ) {
				$elements[] = array( $type->labels->name, get_post_type_archive_link( $post->post_type ) );
			}
		}
		$elements[] = array( get_the_title( $post ), null );
	} elseif ( is_search() ) {
		$elements[] = array( 'Recherche', null );
	} elseif ( is_home() ) {
		$elements[] = array( 'Actualités', null );
	} elseif ( is_post_type_archive() ) {
		$elements[] = array( post_type_archive_title( '', false ), null );
	} elseif ( is_archive() ) {
		$elements[] = array( single_term_title( '', false ) ? single_term_title( '', false ) : wp_strip_all_tags( get_the_archive_title() ), null );
	} elseif ( is_404() ) {
		$elements[] = array( 'Page introuvable', null );
	}
	return $elements;
}

/**
 * HTML du fil d'Ariane (navigation, dernier élément = page en cours).
 *
 * @param int $post_id Contenu concerné.
 * @return string
 */
function vf_socle_html_ariane( $post_id = 0 ) {
	$elements = vf_socle_elements_ariane( $post_id );
	if ( count( $elements ) < 2 ) {
		return '';
	}
	$lis = '';
	foreach ( $elements as $el ) {
		$lis .= $el[1]
			? '<li><a href="' . esc_url( $el[1] ) . '">' . esc_html( $el[0] ) . '</a></li>'
			: '<li aria-current="page">' . esc_html( $el[0] ) . '</li>';
	}
	return '<nav class="ariane" aria-label="Fil d’Ariane"><ol>' . $lis . '</ol></nav>';
}

/**
 * Identifiant du contenu affiché par un bloc (contexte du bloc, sinon page en cours).
 *
 * @param WP_Block $block Bloc.
 * @return int
 */
function vf_socle_post_du_bloc( $block ) {
	if ( ! empty( $block->context['postId'] ) ) {
		return (int) $block->context['postId'];
	}
	return is_singular() ? (int) get_queried_object_id() : 0;
}

/**
 * Vrai pendant l'aperçu d'un bloc dans l'éditeur (rendu côté serveur).
 *
 * @return bool
 */
function vf_socle_apercu_editeur() {
	return defined( 'REST_REQUEST' ) && REST_REQUEST;
}
