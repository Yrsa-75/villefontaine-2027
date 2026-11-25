<?php
/**
 * Plugin Name:       Sécurité Villefontaine
 * Plugin URI:        https://villefontaine.fr
 * Description:       Adresse de connexion personnalisée (wp-login.php et wp-admin masqués aux visiteurs), blocage des adresses IP après plusieurs échecs de connexion, déblocage par un administrateur.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            YRSA
 * License:           GPL-2.0-or-later
 * Text Domain:       securite-villefontaine
 *
 * En cas de problème d'accès : ajouter dans wp-config.php la ligne
 *     define( 'VF_SECURITE_PAUSE', true );
 * Le masquage et les blocages sont alors suspendus et wp-login.php redevient accessible.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

define( 'VF_SECURITE_VERSION', '1.0.0' );
define( 'VF_SECURITE_DIR', plugin_dir_path( __FILE__ ) );

require_once VF_SECURITE_DIR . 'includes/reglages.php';
require_once VF_SECURITE_DIR . 'includes/connexion.php';
require_once VF_SECURITE_DIR . 'includes/blocage.php';
require_once VF_SECURITE_DIR . 'includes/admin.php';

/**
 * À l'activation : réglages par défaut, et rappel de la nouvelle adresse de connexion.
 */
function vf_securite_activation() {
	if ( false === get_option( 'vf_securite_reglages' ) ) {
		add_option( 'vf_securite_reglages', vf_securite_defauts(), '', true );
	}
	add_option( 'vf_securite_blocages', array(), '', false );
	add_option( 'vf_securite_journal', array(), '', false );
	set_transient( 'vf_securite_bienvenue', 1, HOUR_IN_SECONDS );
}
register_activation_hook( __FILE__, 'vf_securite_activation' );
