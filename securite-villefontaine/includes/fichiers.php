<?php
/**
 * Protection des fichiers servis directement par le serveur (règles .htaccess, Apache et LiteSpeed).
 *
 * WordPress ne voit pas passer les demandes de fichiers qui existent sur le disque (readme.html,
 * install.php, un script d'extension appelé directement) : seul le serveur peut les refuser.
 * L'extension écrit donc un bloc de règles, délimité, en tête du fichier .htaccess du site.
 * Les adresses fermées répondent « page introuvable », comme n'importe quelle adresse inexistante.
 *
 * Sécurité de l'opération : après l'écriture, l'extension appelle la page d'accueil du site.
 * Si le serveur répond par une erreur, le fichier d'origine est remis en place aussitôt.
 * Le bloc est retiré à la désactivation de l'extension.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

const VF_SECURITE_BALISE_DEBUT = '# BEGIN Securite Villefontaine';
const VF_SECURITE_BALISE_FIN   = '# END Securite Villefontaine';

/**
 * Chemin du fichier .htaccess du site.
 *
 * @return string
 */
function vf_securite_htaccess_chemin() {
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	/**
	 * Chemin du fichier .htaccess (modifiable pour les essais).
	 *
	 * @param string $chemin Chemin complet.
	 */
	return (string) apply_filters( 'vf_securite_htaccess_chemin', get_home_path() . '.htaccess' );
}

/**
 * Le serveur lit-il les fichiers .htaccess ?
 *
 * @return bool
 */
function vf_securite_htaccess_possible() {
	global $is_apache;
	$logiciel = isset( $_SERVER['SERVER_SOFTWARE'] ) ? (string) wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- seulement comparé.
	/**
	 * Permet de forcer la réponse (essais).
	 *
	 * @param bool $possible Réponse détectée.
	 */
	return (bool) apply_filters( 'vf_securite_htaccess_possible', ! empty( $is_apache ) || false !== stripos( $logiciel, 'litespeed' ) || false !== stripos( $logiciel, 'apache' ) );
}

/**
 * Règles à écrire, selon les réglages.
 *
 * @return string[] Lignes du bloc, sans les balises. Vide si rien n'est à écrire.
 */
function vf_securite_htaccess_regles() {
	$r      = vf_securite_reglages();
	$lignes = array();
	if ( vf_securite_en_pause() ) {
		return $lignes;
	}
	$racine = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$racine = '' === $racine ? '/' : trailingslashit( $racine );
	$cible  = $racine . 'index.php'; // WordPress reçoit la demande et répond « page introuvable ».

	if ( $r['fichiers'] ) {
		$lignes[] = '<IfModule mod_rewrite.c>';
		$lignes[] = 'RewriteEngine On';
		$lignes[] = '# Fichiers d\'installation et de documentation de WordPress.';
		$lignes[] = 'RewriteRule ^(readme\.html|license\.txt|licence\.txt|wp-config-sample\.php|wp-admin/install\.php|wp-admin/maint/repair\.php)$ ' . $cible . ' [L,NC]';
		$lignes[] = '# Mise a niveau de la base : reservee aux personnes connectees.';
		$lignes[] = 'RewriteCond %{HTTP_COOKIE} !wordpress_logged_in_ [NC]';
		$lignes[] = 'RewriteRule ^wp-admin/upgrade\.php$ ' . $cible . ' [L,NC]';
		$lignes[] = '# Anciens acces a distance et envois de commentaires, inutilises sur ce site.';
		$lignes[] = 'RewriteRule ^(xmlrpc\.php|wp-trackback\.php|wp-links-opml\.php|wp-mail\.php' . ( $r['commentaires'] ? '|wp-comments-post\.php' : '' ) . ')$ ' . $cible . ' [L,NC]';
		$lignes[] = '# Scripts internes de WordPress, jamais appeles directement par un navigateur.';
		$lignes[] = 'RewriteRule ^wp-admin/includes/ ' . $cible . ' [L,NC]';
		$lignes[] = 'RewriteRule ^wp-includes/[^/]+\.php$ ' . $cible . ' [L,NC]';
		$lignes[] = 'RewriteRule ^wp-includes/theme-compat/ ' . $cible . ' [L,NC]';
		$lignes[] = '# Aucun script executable dans les medias, ni appel direct des scripts des extensions et des themes.';
		$lignes[] = '# (exception : le script du mode invite de LiteSpeed Cache, appele par le navigateur.)';
		$lignes[] = 'RewriteCond %{REQUEST_URI} !/litespeed-cache/guest\.vary\.php$ [NC]';
		$lignes[] = 'RewriteRule ^wp-content/(uploads|plugins|themes|mu-plugins|languages|upgrade)/.+\.(php[0-9]?|phtml|phar)$ ' . $cible . ' [L,NC]';
		$lignes[] = '# Notices et fichiers de travail des extensions et des themes (ils revelent les versions installees).';
		$lignes[] = 'RewriteRule ^wp-content/(plugins|themes)/.+\.(txt|md|yml|yaml|lock|sh|pot)$ ' . $cible . ' [L,NC]';
		$lignes[] = '# Copies de sauvegarde, exports de base et journaux oublies sur le serveur.';
		$lignes[] = 'RewriteRule \.(bak|old|orig|save|swp|sql|log)$ ' . $cible . ' [L,NC]';
		$lignes[] = '</IfModule>';
	}
	if ( $r['entetes'] ) {
		$lignes[] = '<IfModule mod_headers.c>';
		foreach ( vf_securite_entetes_statiques() as $nom => $valeur ) {
			$lignes[] = 'Header set ' . $nom . ' "' . $valeur . '"';
		}
		$lignes[] = 'Header unset X-Powered-By';
		$lignes[] = 'Header always unset X-Powered-By';
		$lignes[] = '</IfModule>';
	}
	return $lignes;
}

/**
 * En-têtes ajoutés par le serveur aux fichiers qu'il sert lui-même (images, feuilles de style, scripts).
 *
 * @return array
 */
function vf_securite_entetes_statiques() {
	$entetes = vf_securite_entetes();
	if ( 'https' !== wp_parse_url( home_url(), PHP_URL_SCHEME ) ) {
		unset( $entetes['Strict-Transport-Security'] );
	} else {
		$entetes['Strict-Transport-Security'] = 'max-age=31536000';
	}
	return $entetes;
}

/**
 * Contenu d'un fichier .htaccess sans le bloc de l'extension.
 *
 * @param string $contenu Contenu actuel.
 * @return string
 */
function vf_securite_htaccess_sans_bloc( $contenu ) {
	$motif = '~^' . preg_quote( VF_SECURITE_BALISE_DEBUT, '~' ) . '\R.*?^' . preg_quote( VF_SECURITE_BALISE_FIN, '~' ) . '\h*\R?\R?~ms';
	return (string) preg_replace( $motif, '', (string) $contenu );
}

/**
 * État enregistré de la dernière écriture.
 *
 * @return array [ etat, message, quand ] ; etat : actif | retire | manuel | erreur | inconnu.
 */
function vf_securite_htaccess_etat() {
	$etat = get_option( 'vf_securite_fichiers_etat' );
	return is_array( $etat ) ? $etat : array(
		'etat'    => 'inconnu',
		'message' => 'Les règles n’ont pas encore été écrites.',
		'quand'   => 0,
	);
}

/**
 * Enregistre l'état et le note au journal.
 *
 * @param string $etat    actif | retire | manuel | erreur.
 * @param string $message Explication.
 * @return array
 */
function vf_securite_htaccess_noter( $etat, $message ) {
	$donnees = array(
		'etat'    => $etat,
		'message' => $message,
		'quand'   => time(),
	);
	update_option( 'vf_securite_fichiers_etat', $donnees, false );
	vf_securite_journal( 'fichiers', vf_securite_ip(), $message );
	return $donnees;
}

/**
 * Le site répond-il encore correctement ?
 *
 * @return bool|null Vrai : oui. Faux : erreur du serveur. Null : vérification impossible.
 */
function vf_securite_site_repond() {
	$reponse = wp_remote_get(
		add_query_arg( 'vf_securite_verif', time(), home_url( '/' ) ),
		array(
			'timeout'     => 10,
			'redirection' => 2,
			'sslverify'   => false, // Appel du site vers lui-même : seul le code de réponse nous intéresse.
			'headers'     => array( 'Cache-Control' => 'no-cache' ),
		)
	);
	if ( is_wp_error( $reponse ) ) {
		return null;
	}
	return (int) wp_remote_retrieve_response_code( $reponse ) < 500;
}

/**
 * Écrit (ou retire) le bloc de règles dans le fichier .htaccess, puis vérifie que le site répond toujours.
 *
 * @param bool $retirer Vrai pour retirer le bloc (désactivation de l'extension).
 * @param bool $forcer  Vrai pour réécrire et revérifier même si le fichier est déjà à jour.
 * @return array État enregistré.
 */
function vf_securite_htaccess_ecrire( $retirer = false, $forcer = false ) {
	$regles = $retirer ? array() : vf_securite_htaccess_regles();
	if ( ! vf_securite_htaccess_possible() ) {
		return vf_securite_htaccess_noter( 'manuel', 'Ce serveur ne lit pas les fichiers .htaccess : les règles de protection des fichiers ne peuvent pas être écrites automatiquement.' );
	}
	$chemin = vf_securite_htaccess_chemin();
	$avant  = is_readable( $chemin ) ? (string) file_get_contents( $chemin ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier local.
	$reste  = ltrim( vf_securite_htaccess_sans_bloc( $avant ), "\r\n" );
	$apres  = $regles
		? VF_SECURITE_BALISE_DEBUT . "\n# Bloc ecrit par l'extension Securite Villefontaine : ne pas le modifier a la main (il est reecrit a chaque enregistrement des reglages).\n" . implode( "\n", $regles ) . "\n" . VF_SECURITE_BALISE_FIN . "\n\n" . $reste
		: $reste;

	if ( $apres === $avant && ! $forcer ) {
		$etat = vf_securite_htaccess_etat();
		if ( in_array( $etat['etat'], array( 'actif', 'retire' ), true ) ) {
			return $etat; // Rien à changer.
		}
	}
	if ( ( file_exists( $chemin ) && ! is_writable( $chemin ) ) || ( ! file_exists( $chemin ) && ! is_writable( dirname( $chemin ) ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		return vf_securite_htaccess_noter( 'manuel', 'Le fichier .htaccess n’est pas modifiable par WordPress : les règles sont à copier à la main (voir ci-dessous).' );
	}
	if ( false === file_put_contents( $chemin, $apres, LOCK_EX ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return vf_securite_htaccess_noter( 'erreur', 'L’écriture du fichier .htaccess a échoué : rien n’a été modifié.' );
	}

	// Filet de sécurité : si le serveur n'accepte pas ces règles, on remet le fichier d'origine tout de suite.
	$repond = vf_securite_site_repond();
	if ( false === $repond ) {
		file_put_contents( $chemin, $avant, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return vf_securite_htaccess_noter( 'erreur', 'Le serveur a refusé les règles (erreur après écriture) : le fichier .htaccess d’origine a été remis en place automatiquement.' );
	}
	if ( ! $regles ) {
		return vf_securite_htaccess_noter( 'retire', 'Règles retirées du fichier .htaccess.' );
	}
	return vf_securite_htaccess_noter(
		'actif',
		null === $repond
			? 'Règles écrites dans le fichier .htaccess. La vérification automatique n’a pas pu joindre le site : contrôlez qu’il s’affiche normalement.'
			: 'Règles écrites dans le fichier .htaccess, site vérifié après écriture.'
	);
}

/**
 * Réécrit les règles quand un réglage qui les concerne change.
 *
 * @param mixed $avant Anciens réglages.
 * @param mixed $apres Nouveaux réglages.
 */
function vf_securite_htaccess_suivre_reglages( $avant, $apres ) {
	foreach ( array( 'fichiers', 'entetes', 'commentaires' ) as $cle ) {
		if ( (int) ! empty( $avant[ $cle ] ) !== (int) ! empty( $apres[ $cle ] ) ) {
			vf_securite_reglages_oublier();
			vf_securite_htaccess_ecrire();
			return;
		}
	}
}
add_action( 'update_option_vf_securite_reglages', 'vf_securite_htaccess_suivre_reglages', 20, 2 );

/**
 * Après une installation ou une mise à jour de l'extension : les règles sont écrites à la première
 * visite d'un administrateur dans l'administration (jamais pendant la visite d'un internaute).
 */
function vf_securite_htaccess_a_faire() {
	if ( get_option( 'vf_securite_fichiers_a_ecrire' ) && current_user_can( 'manage_options' ) && ! wp_doing_ajax() ) {
		delete_option( 'vf_securite_fichiers_a_ecrire' );
		vf_securite_htaccess_ecrire();
	}
}
add_action( 'admin_init', 'vf_securite_htaccess_a_faire' );
