<?php
/**
 * Redirections permanentes (301) des anciennes adresses du site vers les nouvelles pages.
 * La table est enregistrée lors de la migration (option « vf_redirections » : ancien chemin => ID de page ou adresse).
 * Elle n'est consultée que lorsqu'une page n'est pas trouvée : aucun coût sur les pages existantes.
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalise un chemin : minuscules, sans domaine ni paramètres, avec barre finale.
 *
 * @param string $chemin Chemin ou adresse.
 * @return string
 */
function vf_socle_chemin_normalise( $chemin ) {
	$chemin = (string) wp_parse_url( (string) $chemin, PHP_URL_PATH );
	$chemin = '/' . trim( strtolower( rawurldecode( $chemin ) ), '/' ) . '/';
	return '//' === $chemin ? '/' : $chemin;
}

/**
 * Redirige une ancienne adresse connue.
 */
function vf_socle_rediriger() {
	if ( ! is_404() ) {
		return;
	}
	$table = get_option( 'vf_redirections', array() );
	if ( ! is_array( $table ) || ! $table ) {
		return;
	}
	$chemin = vf_socle_chemin_normalise( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! isset( $table[ $chemin ] ) ) {
		return;
	}
	$cible = $table[ $chemin ];
	$url   = is_numeric( $cible ) ? get_permalink( (int) $cible ) : esc_url_raw( $cible );
	if ( $url && vf_socle_chemin_normalise( $url ) !== $chemin ) {
		wp_safe_redirect( $url, 301, 'Villefontaine' );
		exit;
	}
}
add_action( 'template_redirect', 'vf_socle_rediriger', 1 );

/**
 * Autorise la redirection vers les sites des partenaires éventuellement indiqués dans la table.
 *
 * @param array $hotes Hôtes autorisés.
 * @return array
 */
function vf_socle_hotes_redirection( $hotes ) {
	$hotes[] = 'villefontaine.espace-citoyen.fr';
	return $hotes;
}
add_filter( 'allowed_redirect_hosts', 'vf_socle_hotes_redirection' );
