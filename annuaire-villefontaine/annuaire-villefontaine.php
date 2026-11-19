<?php
/**
 * Plugin Name:       Annuaire Villefontaine
 * Plugin URI:        https://villefontaine.fr
 * Description:       Annuaire des lieux et services de la ville avec carte interactive (Leaflet, fond Plan IGN), filtres par catégorie, fiche par lieu, import et export Excel (CSV).
 * Version:           1.0.5
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Requires Plugins:  socle-villefontaine
 * Author:            YRSA
 * License:           GPL-2.0-or-later
 * Text Domain:       annuaire-villefontaine
 *
 * @package AnnuaireVillefontaine
 */

defined( 'ABSPATH' ) || exit;

define( 'VF_ANNUAIRE_VERSION', '1.0.5' );
define( 'VF_ANNUAIRE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VF_ANNUAIRE_URL', plugin_dir_url( __FILE__ ) );

require_once VF_ANNUAIRE_DIR . 'includes/lieu.php';
require_once VF_ANNUAIRE_DIR . 'includes/gabarits.php';
if ( is_admin() ) {
	require_once VF_ANNUAIRE_DIR . 'includes/import-export.php';
}

/**
 * Déclare les blocs et la bibliothèque de carte (Leaflet 1.9.4, hébergée dans le plugin : aucun appel à un CDN).
 */
function vf_annuaire_blocs() {
	wp_register_style( 'vf-leaflet', VF_ANNUAIRE_URL . 'assets/leaflet/leaflet.css', array(), '1.9.4' );
	wp_register_script( 'vf-leaflet', VF_ANNUAIRE_URL . 'assets/leaflet/leaflet.js', array(), '1.9.4', array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_register_script( 'vf-annuaire-carte', VF_ANNUAIRE_URL . 'blocs/carte-services/vue.js', array(), VF_ANNUAIRE_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	// Leaflet est chargé à la demande par le script de la carte (quand la carte approche de l'écran).
	wp_localize_script(
		'vf-annuaire-carte',
		'vfAnnuaire',
		array(
			'js'  => VF_ANNUAIRE_URL . 'assets/leaflet/leaflet.js?ver=1.9.4',
			'css' => VF_ANNUAIRE_URL . 'assets/leaflet/leaflet.css?ver=1.9.4',
		)
	);
	foreach ( array( 'carte-services', 'lieu-fiche' ) as $nom ) {
		wp_register_script( 'vf-annuaire-' . $nom . '-editeur', VF_ANNUAIRE_URL . 'blocs/' . $nom . '/editeur.js', array( 'vf-socle-editeur' ), VF_ANNUAIRE_VERSION, true );
		register_block_type( VF_ANNUAIRE_DIR . 'blocs/' . $nom );
	}
}
add_action( 'init', 'vf_annuaire_blocs', 20 );

/**
 * À l'activation : types de contenu puis adresses.
 */
function vf_annuaire_activation() {
	vf_annuaire_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'vf_annuaire_activation' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
