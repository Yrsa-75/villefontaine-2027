<?php
/**
 * Réglages du plugin et lecture de l'adresse IP du visiteur.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Réglages par défaut.
 *
 * @return array
 */
function vf_securite_defauts() {
	return array(
		'adresse'    => 'backoffice', // Adresse de la page de connexion : https://site/backoffice.
		'masquer'    => 1,            // Masquer wp-login.php et wp-admin aux visiteurs non connectés.
		'tentatives' => 3,            // Échecs autorisés avant blocage.
		'duree'      => 60,           // Durée du blocage, en minutes.
		'confiance'  => array(),      // Adresses IP jamais bloquées (ex. celle de la mairie).
		'source_ip'  => 'directe',    // D'où lire l'adresse IP : connexion directe, ou en-tête d'un CDN.
		'courriel'   => 1,            // Prévenir l'administrateur à chaque blocage.
	);
}

/**
 * Adresses interdites pour la page de connexion (déjà utilisées par WordPress, ou trop évidentes).
 *
 * @return string[]
 */
function vf_securite_adresses_interdites() {
	return array( '', 'wp-admin', 'wp-login', 'wp-login-php', 'admin', 'login', 'dashboard', 'wp-content', 'wp-includes', 'wp-json', 'feed', 'index-php' );
}

/**
 * Réglages en vigueur, toujours complets et du bon type.
 *
 * @return array
 */
function vf_securite_reglages() {
	$r = wp_parse_args( (array) get_option( 'vf_securite_reglages', array() ), vf_securite_defauts() );

	$r['adresse'] = sanitize_title( (string) $r['adresse'] );
	if ( in_array( $r['adresse'], vf_securite_adresses_interdites(), true ) ) {
		$r['adresse'] = 'backoffice';
	}
	$r['masquer']    = (int) (bool) $r['masquer'];
	$r['courriel']   = (int) (bool) $r['courriel'];
	$r['tentatives'] = max( 1, min( 20, (int) $r['tentatives'] ) );
	$r['duree']      = max( 5, min( 1440, (int) $r['duree'] ) );
	$r['confiance']  = array_values( array_filter( array_map( 'trim', (array) $r['confiance'] ), 'vf_securite_ip_valide' ) );
	if ( ! in_array( $r['source_ip'], array_keys( vf_securite_sources_ip() ), true ) ) {
		$r['source_ip'] = 'directe';
	}
	return $r;
}

/**
 * Interrupteur de secours : define( 'VF_SECURITE_PAUSE', true ) dans wp-config.php suspend tout.
 *
 * @return bool
 */
function vf_securite_en_pause() {
	return defined( 'VF_SECURITE_PAUSE' ) && VF_SECURITE_PAUSE;
}

/**
 * Le masquage de wp-login.php et wp-admin est-il actif ?
 *
 * @return bool
 */
function vf_securite_masquage_actif() {
	return ! vf_securite_en_pause() && vf_securite_reglages()['masquer'];
}

/**
 * Adresse complète de la page de connexion.
 *
 * @return string
 */
function vf_securite_url_connexion() {
	return home_url( '/' . vf_securite_reglages()['adresse'] );
}

/**
 * Une chaîne est-elle une adresse IP valide (v4 ou v6) ?
 *
 * @param mixed $ip Valeur à tester.
 * @return bool
 */
function vf_securite_ip_valide( $ip ) {
	return is_string( $ip ) && false !== filter_var( $ip, FILTER_VALIDATE_IP );
}

/**
 * Sources possibles de l'adresse IP : libellé affiché, puis nom de la variable serveur.
 *
 * @return array
 */
function vf_securite_sources_ip() {
	return array(
		'directe' => array( 'Connexion directe (recommandé, sans CDN)', 'REMOTE_ADDR' ),
		'cf'      => array( 'En-tête CF-Connecting-IP (Cloudflare)', 'HTTP_CF_CONNECTING_IP' ),
		'reelle'  => array( 'En-tête X-Real-IP', 'HTTP_X_REAL_IP' ),
		'relais'  => array( 'En-tête X-Forwarded-For (première adresse)', 'HTTP_X_FORWARDED_FOR' ),
	);
}

/**
 * Adresse IP lue depuis une source donnée.
 *
 * @param string $source Clé de vf_securite_sources_ip().
 * @return string Adresse IP, ou chaîne vide.
 */
function vf_securite_ip_depuis( $source ) {
	$sources = vf_securite_sources_ip();
	$cle     = $sources[ $source ][1] ?? 'REMOTE_ADDR';
	$valeur  = isset( $_SERVER[ $cle ] ) ? trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $cle ] ) ) )[0] ) : '';
	return vf_securite_ip_valide( $valeur ) ? $valeur : '';
}

/**
 * Adresse IP du visiteur, selon la source réglée.
 * Sans CDN, seule la connexion directe est fiable : un en-tête peut être falsifié par le visiteur.
 *
 * @return string Adresse IP, ou chaîne vide si elle est inconnue (tâche planifiée, ligne de commande).
 */
function vf_securite_ip() {
	$ip = vf_securite_ip_depuis( vf_securite_reglages()['source_ip'] );
	return '' !== $ip ? $ip : vf_securite_ip_depuis( 'directe' );
}
