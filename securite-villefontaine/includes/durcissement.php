<?php
/**
 * Protection du site : tout ce qui réduit ce qu'un visiteur malveillant peut voir ou atteindre,
 * sans rien changer pour les agents ni pour le public.
 *
 * - en-têtes de sécurité (anti-détournement dans un cadre, HTTPS imposé au navigateur, etc.) ;
 * - versions de WordPress et de PHP masquées ;
 * - API de WordPress réservée aux personnes connectées (les routes propres au site restent publiques) ;
 * - commentaires et rétroliens désactivés ;
 * - éditeur de fichiers de l'administration désactivé.
 *
 * Les fichiers servis directement par le serveur (readme.html, scripts techniques) sont traités
 * dans fichiers.php, par des règles .htaccess.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/* ---------- En-têtes de sécurité ---------- */

/**
 * En-têtes de sécurité envoyés avec toutes les réponses.
 *
 * @return array nom => valeur.
 */
function vf_securite_entetes() {
	$entetes = array(
		// Le navigateur ne « devine » pas le type d'un fichier : un faux fichier image ne peut pas être exécuté comme un script.
		'X-Content-Type-Options' => 'nosniff',
		// Le site ne peut pas être affiché dans le cadre d'un autre site (piège par superposition).
		'X-Frame-Options'        => 'SAMEORIGIN',
		// Les autres sites ne voient que le nom de domaine d'où vient le visiteur, pas l'adresse complète de la page.
		'Referrer-Policy'        => 'strict-origin-when-cross-origin',
		// Fonctions du navigateur que le site n'utilise pas : coupées, y compris pour les contenus intégrés.
		'Permissions-Policy'     => 'camera=(), microphone=(), payment=(), usb=(), geolocation=(self)',
	);
	if ( is_ssl() ) {
		// Pendant un an, le navigateur refuse de joindre le site autrement qu'en HTTPS.
		$entetes['Strict-Transport-Security'] = 'max-age=31536000';
	}
	return $entetes;
}

/**
 * Politique de contenu des pages publiques et de la page de connexion.
 * Volontairement limitée à ce qui ne peut rien casser : qui peut encadrer la page, où partent les formulaires,
 * pas de contenu actif venu d'ailleurs (les PDF du site affichés dans une page restent possibles),
 * HTTPS pour toutes les ressources quand le site est en HTTPS.
 *
 * @return string
 */
function vf_securite_politique_contenu() {
	return "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'self'" . ( is_ssl() ? '; upgrade-insecure-requests' : '' );
}

/**
 * Envoie les en-têtes, le plus tôt possible.
 */
function vf_securite_envoyer_entetes() {
	if ( headers_sent() || vf_securite_en_pause() || ! vf_securite_reglages()['entetes'] ) {
		return;
	}
	foreach ( vf_securite_entetes() as $nom => $valeur ) {
		header( $nom . ': ' . $valeur );
	}
	// L'administration a ses propres besoins (aperçus, éditeur) : la politique de contenu ne s'applique qu'au reste.
	if ( ! is_admin() || wp_doing_ajax() ) {
		header( 'Content-Security-Policy: ' . vf_securite_politique_contenu() );
	}
	header_remove( 'X-Powered-By' ); // Version de PHP.
}
add_action( 'plugins_loaded', 'vf_securite_envoyer_entetes', 0 );

/**
 * Page de connexion : WordPress y envoie sa propre politique, plus courte, après la nôtre. On remet la nôtre.
 */
function vf_securite_entetes_connexion() {
	if ( ! headers_sent() && ! vf_securite_en_pause() && vf_securite_reglages()['entetes'] ) {
		header( 'Content-Security-Policy: ' . vf_securite_politique_contenu() );
	}
}
add_action( 'login_init', 'vf_securite_entetes_connexion', 20 );

/* ---------- Versions et liens techniques ---------- */

/**
 * Remplace le numéro de version de WordPress, ajouté à l'adresse des feuilles de style et des scripts,
 * par une empreinte : le cache des navigateurs est toujours renouvelé à chaque mise à jour, mais
 * la version n'est plus lisible.
 *
 * @param string $src Adresse du fichier.
 * @return string
 */
function vf_securite_version_discrete( $src ) {
	static $version = null, $empreinte = null;
	if ( null === $version ) {
		$version   = get_bloginfo( 'version' );
		$empreinte = substr( md5( $version . wp_salt( 'nonce' ) ), 0, 8 );
	}
	if ( ! is_string( $src ) || false === strpos( $src, 'ver=' . $version ) ) {
		return $src;
	}
	return preg_replace( '~([?&])ver=' . preg_quote( $version, '~' ) . '(?=&|$)~', '${1}ver=' . $empreinte, $src );
}

/**
 * Même empreinte pour les fichiers de WordPress sans numéro de version propre, y compris quand ils sont regroupés.
 *
 * @param WP_Scripts|WP_Styles $registre Registre des scripts ou des feuilles de style.
 */
function vf_securite_version_par_defaut( $registre ) {
	if ( ! vf_securite_en_pause() && is_object( $registre ) ) {
		$registre->default_version = substr( md5( get_bloginfo( 'version' ) . wp_salt( 'nonce' ) ), 0, 8 );
	}
}
add_action( 'wp_default_scripts', 'vf_securite_version_par_defaut', 99 );
add_action( 'wp_default_styles', 'vf_securite_version_par_defaut', 99 );

/**
 * Retire de la page publique ce qui décrit la mécanique du site.
 */
function vf_securite_discretion() {
	if ( vf_securite_en_pause() ) {
		return;
	}
	add_filter( 'style_loader_src', 'vf_securite_version_discrete', 20 );
	add_filter( 'script_loader_src', 'vf_securite_version_discrete', 20 );
	add_filter( 'script_module_loader_src', 'vf_securite_version_discrete', 20 );
	add_filter( 'the_generator', '__return_empty_string' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

	if ( vf_securite_reglages()['api'] ) {
		// L'API n'étant plus publique, inutile d'en donner l'adresse à chaque page.
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	}
}
add_action( 'init', 'vf_securite_discretion' );

/**
 * L'éditeur de fichiers de l'administration (Apparence > Éditeur de fichiers, Extensions > Éditeur) est désactivé :
 * un compte administrateur volé ne peut pas s'en servir pour modifier le code du site.
 * Les installations et mises à jour restent possibles.
 */
if ( ! vf_securite_en_pause() && ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

/* ---------- API de WordPress ---------- */

/**
 * Une route de l'API est-elle publique ?
 * Les routes propres au site (« vf-… » : recherche, formulaires) gèrent elles-mêmes leurs autorisations.
 *
 * @param string $route Route demandée, par exemple /wp/v2/pages.
 * @return bool
 */
function vf_securite_route_publique( $route ) {
	$route = '/' . ltrim( (string) $route, '/' );
	if ( 0 === strpos( $route, '/vf-' ) ) {
		return true;
	}
	/**
	 * Débuts de routes laissés publics (pour une extension tierce qui en aurait besoin).
	 *
	 * @param string[] $publiques Débuts de routes, sans barre initiale.
	 */
	foreach ( (array) apply_filters( 'vf_securite_routes_publiques', vf_securite_reglages()['api_publiques'] ) as $debut ) {
		$debut = '/' . trim( (string) $debut, '/' );
		if ( '/' !== $debut && ( $route === $debut || 0 === strpos( $route, $debut . '/' ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * L'API de WordPress (liste des pages, des médias, des comptes, description du site) est réservée
 * aux personnes connectées. L'éditeur de l'administration s'en sert ; le site public, non.
 *
 * @param mixed           $resultat Réponse déjà préparée, ou null.
 * @param WP_REST_Server  $serveur  Serveur de l'API.
 * @param WP_REST_Request $requete  Requête.
 * @return mixed
 */
function vf_securite_api_reservee( $resultat, $serveur, $requete ) {
	if ( null !== $resultat || vf_securite_en_pause() || ! vf_securite_reglages()['api'] || is_user_logged_in() ) {
		return $resultat;
	}
	if ( vf_securite_route_publique( $requete->get_route() ) ) {
		return $resultat;
	}
	return new WP_Error( 'rest_reservee', 'Cette ressource est réservée aux personnes connectées.', array( 'status' => 401 ) );
}
add_filter( 'rest_pre_dispatch', 'vf_securite_api_reservee', 10, 3 );

/* ---------- Commentaires et rétroliens ---------- */

/**
 * Les commentaires sont-ils désactivés par l'extension ?
 *
 * @return bool
 */
function vf_securite_sans_commentaires() {
	return ! vf_securite_en_pause() && vf_securite_reglages()['commentaires'];
}

/**
 * Coupe les commentaires et les rétroliens partout : le site n'en publie pas, et c'est la première cible du spam.
 */
function vf_securite_couper_commentaires() {
	if ( ! vf_securite_sans_commentaires() ) {
		return;
	}
	add_filter( 'comments_open', '__return_false', 20 );
	add_filter( 'pings_open', '__return_false', 20 );
	add_filter( 'comments_array', '__return_empty_array', 20 );
	add_filter( 'feed_links_show_comments_feed', '__return_false' );
	add_filter(
		'pre_option_default_comment_status',
		static function () {
			return 'closed';
		}
	);
	add_filter(
		'pre_option_default_ping_status',
		static function () {
			return 'closed';
		}
	);
	foreach ( get_post_types() as $type ) {
		if ( post_type_supports( $type, 'comments' ) ) {
			remove_post_type_support( $type, 'comments' );
		}
		if ( post_type_supports( $type, 'trackbacks' ) ) {
			remove_post_type_support( $type, 'trackbacks' );
		}
	}
}
add_action( 'init', 'vf_securite_couper_commentaires', 100 );

/**
 * Pas d'adresse de rétrolien annoncée dans les en-têtes.
 *
 * @param array $entetes En-têtes de la page.
 * @return array
 */
function vf_securite_sans_pingback( $entetes ) {
	if ( vf_securite_sans_commentaires() ) {
		unset( $entetes['X-Pingback'] );
	}
	return $entetes;
}
add_filter( 'wp_headers', 'vf_securite_sans_pingback' );

/**
 * Le flux des commentaires répond « page introuvable ».
 */
function vf_securite_sans_flux_commentaires() {
	if ( vf_securite_sans_commentaires() && is_comment_feed() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		remove_action( 'do_feed_rss2', 'do_feed_rss2', 10 );
		remove_action( 'do_feed_atom', 'do_feed_atom', 10 );
		wp_die( 'Cette page n’existe pas.', 'Page introuvable', array( 'response' => 404 ) );
	}
}
add_action( 'template_redirect', 'vf_securite_sans_flux_commentaires', 1 );

/**
 * Dans l'administration : le menu « Commentaires » et son raccourci de la barre du haut disparaissent.
 */
function vf_securite_menu_sans_commentaires() {
	if ( vf_securite_sans_commentaires() ) {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}
}
add_action( 'admin_menu', 'vf_securite_menu_sans_commentaires', 999 );

/**
 * Barre d'outils : pas de bulle de commentaires.
 *
 * @param WP_Admin_Bar $barre Barre d'outils.
 */
function vf_securite_barre_sans_commentaires( $barre ) {
	if ( vf_securite_sans_commentaires() ) {
		$barre->remove_node( 'comments' );
	}
}
add_action( 'admin_bar_menu', 'vf_securite_barre_sans_commentaires', 999 );
