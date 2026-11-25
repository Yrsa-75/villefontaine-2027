<?php
/**
 * Adresse de connexion personnalisée.
 *
 * - /backoffice (réglable) affiche la page de connexion de WordPress ;
 * - wp-login.php appelé directement répond « page introuvable » ;
 * - wp-admin répond « page introuvable » aux visiteurs non connectés (au lieu de les renvoyer vers la connexion) ;
 * - tous les liens que WordPress fabrique vers wp-login.php (déconnexion, mot de passe oublié,
 *   courriels, contenus protégés par mot de passe) pointent vers la nouvelle adresse.
 *
 * Une fois connecté, le tableau de bord reste à l'adresse /wp-admin/ : WordPress ne permet pas de la changer.
 * Ce masquage écarte les robots qui visent wp-login.php ; la vraie protection contre le forçage
 * des mots de passe est dans blocage.php.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chemin demandé, relatif à l'accueil du site, sans barre de début ni de fin.
 *
 * @return string
 */
function vf_securite_chemin_demande() {
	$uri    = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- seulement comparé.
	$chemin = (string) wp_parse_url( 'http://x' . ( '/' === substr( $uri, 0, 1 ) ? '' : '/' ) . $uri, PHP_URL_PATH );
	$base   = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( '' !== $base && 0 === strpos( $chemin, $base . '/' ) ) {
		$chemin = substr( $chemin, strlen( $base ) );
	}
	return trim( rawurldecode( $chemin ), '/' );
}

/**
 * La demande en cours vise-t-elle la nouvelle adresse de connexion ?
 *
 * @return bool
 */
function vf_securite_est_la_porte() {
	static $porte = null;
	if ( null === $porte ) {
		$porte = vf_securite_chemin_demande() === vf_securite_reglages()['adresse'];
	}
	return $porte;
}

/**
 * Très tôt : WordPress doit se croire sur sa page de connexion quand on arrive par la nouvelle adresse.
 */
function vf_securite_reperer_la_porte() {
	global $pagenow;
	if ( vf_securite_masquage_actif() && vf_securite_est_la_porte() ) {
		$pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride -- voulu : mêmes comportements que la page de connexion d'origine.
	}
}
add_action( 'plugins_loaded', 'vf_securite_reperer_la_porte', 1 );

/**
 * Remplace wp-login.php par la nouvelle adresse dans une URL (les paramètres sont conservés).
 *
 * @param string $url URL fabriquée par WordPress, absolue ou relative.
 * @return string
 */
function vf_securite_filtrer_url( $url ) {
	if ( ! is_string( $url ) || false === strpos( $url, 'wp-login.php' ) || ! vf_securite_masquage_actif() ) {
		return $url;
	}
	$parties = wp_parse_url( $url );
	// Seules les URL dont le chemin se termine par wp-login.php sont concernées (pas celles qui le citent en paramètre).
	if ( empty( $parties['path'] ) || 'wp-login.php' !== basename( $parties['path'] ) ) {
		return $url;
	}
	$nouvelle = vf_securite_url_connexion();
	if ( ! empty( $parties['scheme'] ) ) {
		$nouvelle = set_url_scheme( $nouvelle, $parties['scheme'] );
	}
	return $nouvelle . ( isset( $parties['query'] ) ? '?' . $parties['query'] : '' );
}
add_filter( 'site_url', 'vf_securite_filtrer_url', 100 );
add_filter( 'network_site_url', 'vf_securite_filtrer_url', 100 );
add_filter( 'wp_redirect', 'vf_securite_filtrer_url', 100 );

/**
 * Répond « page introuvable », exactement comme pour n'importe quelle adresse inexistante du site.
 */
function vf_securite_page_introuvable() {
	global $pagenow;
	$pagenow                = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride -- la demande est traitée comme une page ordinaire.
	$_SERVER['REQUEST_URI'] = user_trailingslashit( rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' ) . '/page-introuvable-' . wp_rand( 1000, 9999 ) );
	if ( ! defined( 'WP_USE_THEMES' ) ) {
		define( 'WP_USE_THEMES', true );
	}
	wp();
	require_once ABSPATH . WPINC . '/template-loader.php';
	exit;
}

/**
 * Aiguillage, une fois WordPress entièrement chargé.
 */
function vf_securite_aiguillage() {
	global $pagenow;
	if ( ! vf_securite_masquage_actif() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return;
	}
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( (string) wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- seulement comparé.

	// 1. Nouvelle adresse : on affiche la page de connexion de WordPress.
	if ( vf_securite_est_la_porte() ) {
		// Ces variables sont déclarées par wp-login.php au niveau global : on les y laisse, ses fonctions en dépendent.
		global $error, $interim_login, $action, $user_login, $errors, $user, $redirect_to, $wp_error; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		require_once ABSPATH . 'wp-login.php';
		exit;
	}

	// 2. wp-login.php appelé directement.
	if ( 'wp-login.php' === $script ) {
		if ( is_user_logged_in() ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
		vf_securite_page_introuvable();
	}

	// 3. wp-admin sans être connecté. Les appels techniques (admin-ajax.php, admin-post.php) restent ouverts : le site public s'en sert.
	if ( is_admin() && ! is_user_logged_in() && ! wp_doing_ajax() && ! in_array( $script, array( 'admin-ajax.php', 'admin-post.php' ), true ) ) {
		// Dans le contexte de l'administration, le thème ne peut pas être affiché proprement : on renvoie vers une adresse inexistante.
		wp_safe_redirect( home_url( '/404' ) );
		exit;
	}
}
add_action( 'wp_loaded', 'vf_securite_aiguillage', 1 );

/**
 * WordPress redirige de lui-même /login, /admin et /dashboard vers la connexion : ce serait indiquer la nouvelle adresse.
 */
function vf_securite_sans_raccourcis() {
	if ( vf_securite_masquage_actif() ) {
		remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
	}
}
add_action( 'init', 'vf_securite_sans_raccourcis' );
