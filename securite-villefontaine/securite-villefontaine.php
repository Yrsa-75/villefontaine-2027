<?php
/**
 * Plugin Name:       Sécurité Villefontaine
 * Plugin URI:        https://villefontaine.fr
 * Description:       Protection de l'administration et du site : adresse de connexion personnalisée, blocage des adresses IP après plusieurs échecs, mots de passe solides, déconnexion après inactivité, alertes, en-têtes de sécurité, fichiers techniques fermés, API réservée, journal d'activité, page de connexion aux couleurs de la Ville.
 * Version:           1.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            YRSA
 * License:           GPL-2.0-or-later
 * Text Domain:       securite-villefontaine
 *
 * En cas de problème d'accès : ajouter dans wp-config.php la ligne
 *     define( 'VF_SECURITE_PAUSE', true );
 * Toutes les protections sont alors suspendues et wp-login.php redevient accessible.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

define( 'VF_SECURITE_VERSION', '1.1.0' );
define( 'VF_SECURITE_FICHIER', __FILE__ );
define( 'VF_SECURITE_DIR', plugin_dir_path( __FILE__ ) );

require_once VF_SECURITE_DIR . 'includes/reglages.php';
require_once VF_SECURITE_DIR . 'includes/journal.php';
require_once VF_SECURITE_DIR . 'includes/connexion.php';
require_once VF_SECURITE_DIR . 'includes/blocage.php';
require_once VF_SECURITE_DIR . 'includes/comptes.php';
require_once VF_SECURITE_DIR . 'includes/durcissement.php';
require_once VF_SECURITE_DIR . 'includes/fichiers.php';
require_once VF_SECURITE_DIR . 'includes/habillage.php';
require_once VF_SECURITE_DIR . 'includes/admin.php';

/**
 * Après une installation ou une mise à jour de l'extension : table du journal, purge quotidienne,
 * et règles de protection des fichiers à réécrire (à la prochaine visite d'un administrateur).
 */
function vf_securite_mise_a_niveau() {
	if ( VF_SECURITE_VERSION === get_option( 'vf_securite_version' ) ) {
		return;
	}
	vf_securite_journal_installer();
	update_option( 'vf_securite_version', VF_SECURITE_VERSION, true );
	update_option( 'vf_securite_fichiers_a_ecrire', 1, false );
}
add_action( 'init', 'vf_securite_mise_a_niveau', 0 );

/**
 * À l'activation : réglages par défaut, et rappel de l'adresse de connexion.
 */
function vf_securite_activation() {
	if ( false === get_option( 'vf_securite_reglages' ) ) {
		add_option( 'vf_securite_reglages', vf_securite_defauts(), '', true );
	}
	add_option( 'vf_securite_blocages', array(), '', false );
	delete_option( 'vf_securite_version' ); // Force la mise à niveau (table, règles) à la prochaine requête.
	set_transient( 'vf_securite_bienvenue', 1, HOUR_IN_SECONDS );
}
register_activation_hook( __FILE__, 'vf_securite_activation' );

/**
 * À la désactivation : les règles écrites dans le fichier .htaccess sont retirées, la purge quotidienne est arrêtée.
 * Le journal et les réglages sont conservés (ils sont supprimés à la désinstallation, voir uninstall.php).
 */
function vf_securite_desactivation() {
	vf_securite_htaccess_ecrire( true );
	wp_clear_scheduled_hook( 'vf_securite_purge' );
	delete_option( 'vf_securite_version' );
}
register_deactivation_hook( __FILE__, 'vf_securite_desactivation' );
