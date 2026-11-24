<?php
/**
 * Plugin Name:       Actualités Villefontaine
 * Plugin URI:        https://villefontaine.fr
 * Description:       Actualités de la ville : mise « À la une », mosaïque des dernières actualités pour l'accueil et les rubriques.
 * Version:           1.0.3
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  socle-villefontaine
 * Author:            YRSA
 * License:           GPL-2.0-or-later
 * Text Domain:       actualites-villefontaine
 *
 * @package ActualitesVillefontaine
 */

defined( 'ABSPATH' ) || exit;

define( 'VF_ACTUS_VERSION', '1.0.3' );
define( 'VF_ACTUS_DIR', plugin_dir_path( __FILE__ ) );
define( 'VF_ACTUS_URL', plugin_dir_url( __FILE__ ) );

require_once VF_ACTUS_DIR . 'includes/une.php';

/**
 * Déclare le bloc « Actualités en mosaïque ».
 */
function vf_actus_blocs() {
	wp_register_script( 'vf-actus-mosaique-editeur', VF_ACTUS_URL . 'blocs/actualites-mosaique/editeur.js', array( 'vf-socle-editeur' ), VF_ACTUS_VERSION, true );
	register_block_type( VF_ACTUS_DIR . 'blocs/actualites-mosaique' );
}
add_action( 'init', 'vf_actus_blocs', 20 );
