<?php
/**
 * Encart « Contact » des pages : champs saisis dans la colonne de droite de l'éditeur
 * (panneau « Encart contact »), affichés par le bloc vf/encart-contact du gabarit de page.
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Champs de l'encart : clé => [type, fonction de nettoyage].
 *
 * @return array
 */
function vf_socle_champs_contact() {
	return array(
		'vf_contact_titre'    => array( 'string', 'sanitize_text_field' ),
		'vf_contact_adresse'  => array( 'string', 'sanitize_textarea_field' ),
		'vf_contact_tel'      => array( 'string', 'sanitize_text_field' ),
		'vf_contact_courriel' => array( 'string', 'sanitize_email' ),
		'vf_contact_lien'     => array( 'string', 'esc_url_raw' ),
		'vf_contact_horaires' => array( 'string', 'sanitize_textarea_field' ),
		'vf_contact_pmr'      => array( 'boolean', 'rest_sanitize_boolean' ),
		'vf_icone'            => array( 'string', 'sanitize_key' ),
	);
}

/**
 * Déclare les champs (lisibles par l'éditeur, modifiables seulement par qui peut modifier la page).
 */
function vf_socle_meta_contact() {
	foreach ( vf_socle_champs_contact() as $cle => $def ) {
		register_post_meta(
			'page',
			$cle,
			array(
				'type'              => $def[0],
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => ( 'boolean' === $def[0] ) ? false : '',
				'sanitize_callback' => $def[1],
				'auth_callback'     => function ( $autorise, $cle_meta, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
add_action( 'init', 'vf_socle_meta_contact' );

/**
 * Panneau « Encart contact » dans la colonne de droite de l'éditeur de pages.
 */
function vf_socle_panneau_contact() {
	$ecran = get_current_screen();
	if ( ! $ecran || 'page' !== $ecran->post_type ) {
		return;
	}
	wp_enqueue_script(
		'vf-socle-panneau-contact',
		VF_SOCLE_URL . 'assets/js/panneau-contact.js',
		array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data' ),
		VF_SOCLE_VERSION,
		true
	);
	wp_localize_script( 'vf-socle-panneau-contact', 'vfSocleIcones', vf_socle_icones_libelles() );
}
add_action( 'enqueue_block_editor_assets', 'vf_socle_panneau_contact' );
