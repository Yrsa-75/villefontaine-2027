<?php
/**
 * Plugin Name:       Socle Villefontaine
 * Plugin URI:        https://villefontaine.fr
 * Description:       Blocs de mise en page du site de la Ville de Villefontaine : en-tête et méga-menu, panneau « Vos démarches », grand visuel d'accueil, accès en 1 clic, sections, bandeau de page, sommaire, encart contact, sous-pages.
 * Version:           1.3.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            YRSA
 * License:           GPL-2.0-or-later
 * Text Domain:       socle-villefontaine
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

define( 'VF_SOCLE_VERSION', '1.3.0' );
define( 'VF_SOCLE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VF_SOCLE_URL', plugin_dir_url( __FILE__ ) );

require_once VF_SOCLE_DIR . 'includes/icones.php';
require_once VF_SOCLE_DIR . 'includes/outils.php';
require_once VF_SOCLE_DIR . 'includes/menus.php';
require_once VF_SOCLE_DIR . 'includes/contact.php';
require_once VF_SOCLE_DIR . 'includes/blocs.php';
require_once VF_SOCLE_DIR . 'includes/redirections.php';
require_once VF_SOCLE_DIR . 'includes/formulaire.php';

if ( is_admin() ) {
	require_once VF_SOCLE_DIR . 'includes/admin.php';
}
