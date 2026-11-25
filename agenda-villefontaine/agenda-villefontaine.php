<?php
/**
 * Plugin Name:       Agenda Villefontaine
 * Plugin URI:        https://villefontaine.fr
 * Description:       Agenda de la ville : événements ponctuels, sur plusieurs jours ou récurrents, thématiques, prochains rendez-vous sur l'accueil, page Agenda avec filtres.
 * Version:           1.1.4
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Requires Plugins:  socle-villefontaine
 * Author:            YRSA
 * License:           GPL-2.0-or-later
 * Text Domain:       agenda-villefontaine
 *
 * @package AgendaVillefontaine
 */

defined( 'ABSPATH' ) || exit;

define( 'VF_AGENDA_VERSION', '1.1.4' );
define( 'VF_AGENDA_DIR', plugin_dir_path( __FILE__ ) );
define( 'VF_AGENDA_URL', plugin_dir_url( __FILE__ ) );

require_once VF_AGENDA_DIR . 'includes/evenement.php';
require_once VF_AGENDA_DIR . 'includes/occurrences.php';
require_once VF_AGENDA_DIR . 'includes/gabarits.php';

/**
 * Déclare les blocs de l'agenda.
 */
function vf_agenda_blocs() {
	wp_register_script( 'vf-agenda-filtres', VF_AGENDA_URL . 'assets/js/filtres.js', array(), VF_AGENDA_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	foreach ( array( 'agenda-prochains', 'agenda-liste', 'evenement-infos' ) as $nom ) {
		wp_register_script( 'vf-agenda-' . $nom . '-editeur', VF_AGENDA_URL . 'blocs/' . $nom . '/editeur.js', array( 'vf-socle-editeur' ), VF_AGENDA_VERSION, true );
		register_block_type( VF_AGENDA_DIR . 'blocs/' . $nom );
	}
}
add_action( 'init', 'vf_agenda_blocs', 20 );

/**
 * À l'activation : enregistre le type de contenu puis régénère les adresses (/agenda/…).
 */
function vf_agenda_activation() {
	vf_agenda_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'vf_agenda_activation' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
