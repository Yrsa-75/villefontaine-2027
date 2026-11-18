<?php
/**
 * Plugin Name:       Kiosque Villefontaine
 * Plugin URI:        https://villefontaine.fr
 * Description:       Publications de la ville (magazine, guides…) : couvertures en éventail, lecteur Calaméo chargé uniquement après accord du visiteur, téléchargement PDF.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Requires Plugins:  socle-villefontaine
 * Author:            YRSA
 * License:           GPL-2.0-or-later
 * Text Domain:       kiosque-villefontaine
 *
 * @package KiosqueVillefontaine
 */

defined( 'ABSPATH' ) || exit;

define( 'VF_KIOSQUE_VERSION', '1.0.0' );
define( 'VF_KIOSQUE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VF_KIOSQUE_URL', plugin_dir_url( __FILE__ ) );

require_once VF_KIOSQUE_DIR . 'includes/publication.php';

/**
 * Déclare le bloc « Kiosque ».
 */
function vf_kiosque_blocs() {
	wp_register_script( 'vf-kiosque-editeur', VF_KIOSQUE_URL . 'blocs/kiosque/editeur.js', array( 'vf-socle-editeur' ), VF_KIOSQUE_VERSION, true );
	wp_register_script( 'vf-kiosque-vue', VF_KIOSQUE_URL . 'blocs/kiosque/vue.js', array(), VF_KIOSQUE_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	register_block_type( VF_KIOSQUE_DIR . 'blocs/kiosque' );
}
add_action( 'init', 'vf_kiosque_blocs', 20 );

/**
 * À l'activation : type de contenu puis adresses.
 */
function vf_kiosque_activation() {
	vf_kiosque_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'vf_kiosque_activation' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
