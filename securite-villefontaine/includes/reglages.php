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
		'recidive'   => 1,            // Deuxième blocage en 24 heures : l'adresse est bloquée 24 heures.

		// Protection du site.
		'entetes'       => 1,         // En-têtes de sécurité envoyés avec chaque page.
		'fichiers'      => 1,         // Règles .htaccess : fichiers d'installation et scripts techniques fermés.
		'api'           => 1,         // API REST de WordPress réservée aux personnes connectées.
		'api_publiques' => array(),   // Débuts de routes laissés publics en plus de celles du site (vf-…).
		'commentaires'  => 1,         // Commentaires et rétroliens désactivés.

		// Comptes.
		'mdp_regles'      => 1,       // Mots de passe solides imposés.
		'mdp_longueur'    => 12,      // Longueur minimale.
		'inactivite'      => 120,     // Déconnexion après tant de minutes sans activité (0 = jamais).
		'alerte_appareil' => 1,       // Courriel au titulaire du compte à chaque connexion depuis un nouvel appareil.
		'conservation'    => 180,     // Durée de conservation du journal, en jours.

		// Page de connexion.
		'habillage'   => 1,           // Page de connexion aux couleurs de la Ville.
		'porte_titre' => 'Espace des agents',
		'porte_texte' => 'Publiez les actualités, l’agenda et les pages du site de la Ville.',
		'porte_image' => 0,           // Photo de la médiathèque (0 = photo fournie avec l'extension).
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
	global $vf_securite_reglages_lus;
	if ( is_array( $vf_securite_reglages_lus ) ) {
		return $vf_securite_reglages_lus;
	}
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
	foreach ( array( 'recidive', 'entetes', 'fichiers', 'api', 'commentaires', 'mdp_regles', 'alerte_appareil', 'habillage' ) as $cle ) {
		$r[ $cle ] = (int) (bool) $r[ $cle ];
	}
	$r['mdp_longueur']  = max( 8, min( 64, (int) $r['mdp_longueur'] ) );
	$r['inactivite']    = 0 === (int) $r['inactivite'] ? 0 : max( 15, min( 1440, (int) $r['inactivite'] ) );
	$r['conservation']  = max( 30, min( 365, (int) $r['conservation'] ) );
	$r['porte_titre']   = mb_substr( sanitize_text_field( (string) $r['porte_titre'] ), 0, 60 );
	$r['porte_texte']   = mb_substr( sanitize_text_field( (string) $r['porte_texte'] ), 0, 200 );
	$r['porte_image']   = absint( $r['porte_image'] );
	$r['api_publiques'] = array_values( array_filter( array_map( 'vf_securite_route_propre', (array) $r['api_publiques'] ) ) );

	$vf_securite_reglages_lus = $r;
	return $r;
}

/**
 * Les réglages sont relus après chaque enregistrement.
 */
function vf_securite_reglages_oublier() {
	global $vf_securite_reglages_lus;
	$vf_securite_reglages_lus = null;
}
add_action( 'update_option_vf_securite_reglages', 'vf_securite_reglages_oublier' );
add_action( 'add_option_vf_securite_reglages', 'vf_securite_reglages_oublier' );

/**
 * Début de route de l'API, nettoyé : « contact-form-7/v1 ».
 *
 * @param mixed $route Valeur saisie.
 * @return string
 */
function vf_securite_route_propre( $route ) {
	return trim( preg_replace( '~[^a-z0-9/_-]~', '', strtolower( (string) $route ) ), '/' );
}

/**
 * Durée écrite en clair : « 1 heure », « 24 heures », « 45 minutes ».
 *
 * @param int $minutes Durée en minutes.
 * @return string
 */
function vf_securite_duree_en_clair( $minutes ) {
	$minutes = (int) $minutes;
	if ( $minutes >= 60 && 0 === $minutes % 60 ) {
		$h = (int) ( $minutes / 60 );
		return 1 === $h ? '1 heure' : $h . ' heures';
	}
	return 1 === $minutes ? '1 minute' : $minutes . ' minutes';
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
