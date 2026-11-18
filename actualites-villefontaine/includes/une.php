<?php
/**
 * Mise « À la une » d'une actualité.
 *
 * L'agent coche « Mettre à la une » dans la colonne de droite de l'article.
 * La mosaïque affiche en grand l'actualité à la une la plus récente.
 *
 * @package ActualitesVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Déclare le champ « à la une ».
 */
function vf_actus_meta() {
	register_post_meta(
		'post',
		'vf_a_la_une',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'default'           => false,
			'show_in_rest'      => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'auth_callback'     => function ( $autorise, $cle, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		)
	);
}
add_action( 'init', 'vf_actus_meta' );

/**
 * Case « Mettre à la une » dans l'éditeur des articles.
 */
function vf_actus_panneau_une() {
	$ecran = get_current_screen();
	if ( ! $ecran || 'post' !== $ecran->post_type ) {
		return;
	}
	wp_enqueue_script( 'vf-actus-panneau-une', VF_ACTUS_URL . 'assets/js/panneau-une.js', array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data' ), VF_ACTUS_VERSION, true );
}
add_action( 'enqueue_block_editor_assets', 'vf_actus_panneau_une' );

/**
 * Colonne « À la une » dans la liste des articles.
 *
 * @param array $colonnes Colonnes.
 * @return array
 */
function vf_actus_colonne( $colonnes ) {
	$colonnes['vf_une'] = 'À la une';
	return $colonnes;
}
add_filter( 'manage_post_posts_columns', 'vf_actus_colonne' );

/**
 * Contenu de la colonne.
 *
 * @param string $colonne Colonne.
 * @param int    $post_id Article.
 */
function vf_actus_colonne_contenu( $colonne, $post_id ) {
	if ( 'vf_une' === $colonne ) {
		echo get_post_meta( $post_id, 'vf_a_la_une', true ) ? '<span aria-hidden="true">★</span> <span>Oui</span>' : '<span class="screen-reader-text">Non</span>';
	}
}
add_action( 'manage_post_posts_custom_column', 'vf_actus_colonne_contenu', 10, 2 );

/**
 * Actualités à afficher : 1 à la une + les plus récentes.
 *
 * @param int $nombre    Nombre total.
 * @param int $categorie Catégorie (0 = toutes).
 * @return WP_Post[]
 */
function vf_actus_selection( $nombre, $categorie = 0 ) {
	$base = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $categorie ) {
		$base['cat'] = (int) $categorie;
	}
	$une = get_posts(
		$base + array(
			'posts_per_page' => 1,
			'meta_key'       => 'vf_a_la_une', // phpcs:ignore WordPress.DB.SlowDBQuery -- une seule ligne, requête mise en cache par LiteSpeed.
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$autres = get_posts(
		$base + array(
			'posts_per_page' => $nombre - count( $une ),
			'post__not_in'   => wp_list_pluck( $une, 'ID' ),
		)
	);
	return array_merge( $une, $autres );
}
