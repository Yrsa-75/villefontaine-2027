<?php
/**
 * Plugin Name:       Déploiement Villefontaine
 * Plugin URI:        https://github.com/Yrsa-75/villefontaine-2027
 * Description:       Installe et met à jour le thème et les plugins Villefontaine depuis le dépôt GitHub du projet, et récupère les images du site actuel. Réservé aux administrateurs.
 * Version:           1.3.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            YRSA
 * License:           GPL-2.0-or-later
 * Text Domain:       deploiement-villefontaine
 *
 * @package DeploiementVillefontaine
 */

defined( 'ABSPATH' ) || exit;

define( 'VF_DEPLOI_VERSION', '1.3.0' );

/**
 * Dépôt GitHub (public) qui contient les fichiers .zip prêts à installer dans dist/.
 */
if ( ! defined( 'VF_DEPLOI_DEPOT' ) ) {
	define( 'VF_DEPLOI_DEPOT', 'Yrsa-75/villefontaine-2027' );
}

/**
 * Éléments autorisés : rien d'autre ne peut être installé par ce plugin.
 *
 * @return array slug => [ type, libellé, fichier principal (plugins) ].
 */
function vf_deploi_elements() {
	return array(
		'socle-villefontaine'       => array( 'plugin', 'Socle (blocs de mise en page)', 'socle-villefontaine/socle-villefontaine.php' ),
		'villefontaine'             => array( 'theme', 'Thème Villefontaine', '' ),
		'actualites-villefontaine'  => array( 'plugin', 'Actualités', 'actualites-villefontaine/actualites-villefontaine.php' ),
		'agenda-villefontaine'      => array( 'plugin', 'Agenda', 'agenda-villefontaine/agenda-villefontaine.php' ),
		'annuaire-villefontaine'    => array( 'plugin', 'Annuaire et carte', 'annuaire-villefontaine/annuaire-villefontaine.php' ),
		'kiosque-villefontaine'     => array( 'plugin', 'Kiosque', 'kiosque-villefontaine/kiosque-villefontaine.php' ),
		'deploiement-villefontaine' => array( 'plugin', 'Déploiement (ce plugin)', 'deploiement-villefontaine/deploiement-villefontaine.php' ),
	);
}

/**
 * Domaines depuis lesquels des images peuvent être récupérées.
 *
 * @return string[]
 */
function vf_deploi_domaines_images() {
	return array( 'villefontaine.fr', 'www.villefontaine.fr', 'www.calameo.com' ); // Calaméo : uniquement les couvertures (chemin /books/social/cover/), copiées une fois sur le site.
}

/**
 * Droit requis pour toutes les actions.
 *
 * @return bool
 */
function vf_deploi_autorise() {
	return current_user_can( 'manage_options' ) && current_user_can( 'install_plugins' ) && current_user_can( 'install_themes' );
}

/**
 * Lit le manifeste dist/versions.json du dernier état du dépôt (adresse figée sur le commit : pas de cache périmé).
 *
 * @return array|WP_Error [ 'commit' => sha, 'versions' => [ slug => version ] ].
 */
function vf_deploi_manifeste() {
	$rep = wp_remote_get(
		'https://api.github.com/repos/' . VF_DEPLOI_DEPOT . '/commits/main',
		array(
			'timeout' => 15,
			'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'deploiement-villefontaine' ),
		)
	);
	if ( is_wp_error( $rep ) || 200 !== wp_remote_retrieve_response_code( $rep ) ) {
		return new WP_Error( 'github', 'Impossible de joindre le dépôt GitHub.' );
	}
	$sha = json_decode( wp_remote_retrieve_body( $rep ), true )['sha'] ?? '';
	if ( ! preg_match( '/^[0-9a-f]{40}$/', $sha ) ) {
		return new WP_Error( 'github', 'Réponse inattendue de GitHub.' );
	}
	$rep = wp_remote_get( 'https://raw.githubusercontent.com/' . VF_DEPLOI_DEPOT . '/' . $sha . '/dist/versions.json', array( 'timeout' => 15 ) );
	if ( is_wp_error( $rep ) || 200 !== wp_remote_retrieve_response_code( $rep ) ) {
		return new WP_Error( 'github', 'Manifeste dist/versions.json introuvable.' );
	}
	$versions = json_decode( wp_remote_retrieve_body( $rep ), true );
	return array(
		'commit'   => $sha,
		'versions' => is_array( $versions ) ? $versions : array(),
	);
}

/**
 * Version installée d'un élément (ou chaîne vide).
 *
 * @param string $slug Élément.
 * @return string
 */
function vf_deploi_version_installee( $slug ) {
	$el = vf_deploi_elements()[ $slug ];
	if ( 'theme' === $el[0] ) {
		$theme = wp_get_theme( $slug );
		return $theme->exists() ? (string) $theme->get( 'Version' ) : '';
	}
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugins = get_plugins();
	return isset( $plugins[ $el[2] ] ) ? (string) $plugins[ $el[2] ]['Version'] : '';
}

/**
 * État de tous les éléments.
 *
 * @return array|WP_Error
 */
function vf_deploi_etat() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$m = vf_deploi_manifeste();
	if ( is_wp_error( $m ) ) {
		return $m;
	}
	$etat = array();
	foreach ( vf_deploi_elements() as $slug => $el ) {
		$etat[ $slug ] = array(
			'libelle'    => $el[1],
			'installee'  => vf_deploi_version_installee( $slug ),
			'disponible' => isset( $m['versions'][ $slug ] ) ? sanitize_text_field( $m['versions'][ $slug ] ) : '',
			'active'     => 'theme' === $el[0] ? get_stylesheet() === $slug : is_plugin_active( $el[2] ),
		);
	}
	return array( 'commit' => $m['commit'], 'elements' => $etat );
}

/**
 * Installe ou met à jour un élément depuis le dépôt, puis l'active.
 *
 * @param string $slug Élément autorisé.
 * @return array|WP_Error
 */
function vf_deploi_installer( $slug ) {
	$elements = vf_deploi_elements();
	if ( ! isset( $elements[ $slug ] ) ) {
		return new WP_Error( 'interdit', 'Élément non autorisé.' );
	}
	if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
		return new WP_Error( 'interdit', 'Les installations sont désactivées sur ce site (DISALLOW_FILE_MODS).' );
	}
	$m = vf_deploi_manifeste();
	if ( is_wp_error( $m ) ) {
		return $m;
	}
	if ( empty( $m['versions'][ $slug ] ) ) {
		return new WP_Error( 'absent', 'Cet élément n’est pas encore publié dans le dépôt.' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/theme.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	$paquet = 'https://raw.githubusercontent.com/' . VF_DEPLOI_DEPOT . '/' . $m['commit'] . '/dist/' . $slug . '.zip';
	$el     = $elements[ $slug ];
	$avant  = vf_deploi_version_installee( $slug );
	$skin   = new WP_Ajax_Upgrader_Skin();
	$up     = 'theme' === $el[0] ? new Theme_Upgrader( $skin ) : new Plugin_Upgrader( $skin );
	$res    = $up->install( $paquet, array( 'overwrite_package' => true ) );

	if ( is_wp_error( $res ) ) {
		return $res;
	}
	if ( is_wp_error( $skin->result ) ) {
		return $skin->result;
	}
	if ( ! $res ) {
		$err = $skin->get_errors();
		return new WP_Error( 'echec', $err->has_errors() ? $err->get_error_message() : 'Installation impossible (droits d’écriture du serveur ?).' );
	}

	// Activation (sauf pour ce plugin lui-même, déjà actif).
	if ( 'plugin' === $el[0] ) {
		wp_clean_plugins_cache();
		if ( ! is_plugin_active( $el[2] ) ) {
			$act = activate_plugin( $el[2] );
			if ( is_wp_error( $act ) ) {
				return $act;
			}
		}
	} elseif ( get_stylesheet() !== $slug ) {
		switch_theme( $slug );
	}
	// Les adresses (/agenda/, /kiosque/…) sont régénérées au prochain chargement.
	update_option( 'vf_deploi_regenerer_adresses', 1 );

	return array(
		'element' => $slug,
		'avant'   => $avant,
		'apres'   => vf_deploi_version_installee( $slug ),
	);
}

add_action(
	'init',
	function () {
		if ( get_option( 'vf_deploi_regenerer_adresses' ) ) {
			delete_option( 'vf_deploi_regenerer_adresses' );
			flush_rewrite_rules();
		}
	},
	99
);

/**
 * Importe une image du site actuel dans la médiathèque (une seule fois par adresse).
 *
 * @param string $url     Adresse de l'image (domaines autorisés uniquement).
 * @param string $alt     Texte alternatif.
 * @return array|WP_Error
 */
function vf_deploi_importer_image( $url, $alt = '' ) {
	$url  = esc_url_raw( $url );
	$hote = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) || ! in_array( $hote, vf_deploi_domaines_images(), true ) ) {
		return new WP_Error( 'interdit', 'Seules les images de villefontaine.fr et les couvertures Calaméo peuvent être importées.' );
	}
	$chemin = (string) wp_parse_url( $url, PHP_URL_PATH );
	$calameo = 'www.calameo.com' === $hote;
	if ( $calameo ? ! preg_match( '#^/books/social/cover/[A-Za-z0-9]+$#', $chemin ) : ! preg_match( '/\.(jpe?g|png|gif|webp|pdf|docx?|xlsx?|odt|ods|pptx?|zip)$/i', $chemin ) ) {
		return new WP_Error( 'format', 'Format de fichier non accepté.' );
	}
	$deja = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'vf_source', // phpcs:ignore WordPress.DB.SlowDBQuery -- import ponctuel.
			'meta_value'     => $url, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $deja ) {
		return array( 'id' => (int) $deja[0], 'existant' => true );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	// Certains hébergeurs d'images (dont Calaméo) refusent les téléchargements qui se présentent comme « WordPress ».
	$entetes = function ( $args, $adresse ) use ( $url ) {
		if ( $adresse === $url ) {
			$args['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';
			$args['headers']    = array_merge( (array) ( $args['headers'] ?? array() ), array( 'Referer' => 'https://www.calameo.com/', 'Accept' => 'image/avif,image/webp,image/*,*/*;q=0.8' ) );
		}
		return $args;
	};
	add_filter( 'http_request_args', $entetes, 10, 2 );
	$tmp = download_url( $url, 30 );
	remove_filter( 'http_request_args', $entetes, 10 );
	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}
	$recadree = $calameo && vf_deploi_recadrer_couverture( $tmp );
	$nom = $calameo ? 'couverture-' . basename( $chemin ) . '.jpg' : wp_basename( $chemin );
	// WordPress vérifie ensuite le vrai type du fichier : un fichier qui n'est pas une image est refusé.
	$id = media_handle_sideload( array( 'name' => sanitize_file_name( $nom ), 'tmp_name' => $tmp ), 0 );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
	}
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	update_post_meta( $id, 'vf_source', $url );
	if ( $recadree ) {
		update_post_meta( $id, 'vf_recadree', 1 );
	}
	if ( $alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
	}
	return array( 'id' => (int) $id, 'existant' => false );
}

/**
 * Couvertures Calaméo : l'image que fournit Calaméo est une vignette de partage (1200 × 628 px)
 * où la couverture est posée au centre d'un fond gris foncé, avec le logo Calaméo.
 * On ne garde que la couverture, sinon elle apparaît entourée d'un cadre sombre dans le kiosque.
 *
 * Principe : la couleur du fond est lue dans un coin ; on cherche, depuis la gauche puis depuis le haut,
 * le premier point qui s'en écarte nettement. La couverture étant centrée, les bords droit et bas
 * sont symétriques (on évite ainsi l'ombre portée, plus marquée de ce côté).
 *
 * @param string $fichier Chemin du fichier image, modifié sur place.
 * @return bool Vrai si l'image a été recadrée.
 */
function vf_deploi_recadrer_couverture( $fichier ) {
	if ( ! function_exists( 'imagecreatefromstring' ) || ! is_readable( $fichier ) || ! wp_is_writable( $fichier ) ) {
		return false;
	}
	$donnees = file_get_contents( $fichier ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- fichier local.
	$infos   = $donnees ? getimagesizefromstring( $donnees ) : false;
	$image   = $infos ? @imagecreatefromstring( $donnees ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors -- un fichier illisible renvoie simplement false.
	if ( ! $image ) {
		return false;
	}
	if ( ! imageistruecolor( $image ) ) {
		imagepalettetotruecolor( $image );
	}
	$largeur = imagesx( $image );
	$hauteur = imagesy( $image );
	// Une image déjà en portrait est une vraie couverture : on n'y touche pas.
	if ( $largeur < 400 || $hauteur < 200 || $largeur <= $hauteur ) {
		return false;
	}
	$fond  = imagecolorat( $image, 3, 3 );
	$ecart = static function ( $x, $y ) use ( $image, $fond ) {
		$c = imagecolorat( $image, $x, $y );
		return abs( ( ( $c >> 16 ) & 255 ) - ( ( $fond >> 16 ) & 255 ) ) + abs( ( ( $c >> 8 ) & 255 ) - ( ( $fond >> 8 ) & 255 ) ) + abs( ( $c & 255 ) - ( $fond & 255 ) );
	};
	$seuil = 60; // L'ombre portée autour de la couverture s'écarte du fond de 25 au plus de ce côté.

	// Bord gauche : le point le plus à gauche trouvé sur une trentaine de lignes.
	$gauche = null;
	for ( $y = (int) ( $hauteur * 0.15 ); $y < $hauteur * 0.85; $y += max( 1, (int) ( $hauteur / 40 ) ) ) {
		for ( $x = 0; $x < $largeur / 2; $x++ ) {
			if ( $ecart( $x, $y ) > $seuil ) {
				$gauche = null === $gauche ? $x : min( $gauche, $x );
				break;
			}
		}
	}
	if ( null === $gauche || $gauche < 8 ) {
		return false; // Pas de fond uni autour : ce n'est pas une vignette de partage.
	}
	$droite = $largeur - $gauche;

	// Bord haut : même recherche, sur les colonnes de la couverture.
	$haut = null;
	$pas  = max( 1, (int) ( ( $droite - $gauche ) / 30 ) );
	for ( $x = $gauche + $pas; $x < $droite - $pas; $x += $pas ) {
		for ( $y = 0; $y < $hauteur / 2; $y++ ) {
			if ( $ecart( $x, $y ) > $seuil ) {
				$haut = null === $haut ? $y : min( $haut, $y );
				break;
			}
		}
	}
	if ( null === $haut ) {
		return false;
	}
	$bas = $hauteur - $haut;

	// Un point de marge de chaque côté : le bord de la couverture est légèrement fondu avec le fond.
	$zone = array(
		'x'      => $gauche + 1,
		'y'      => $haut + 1,
		'width'  => $droite - $gauche - 2,
		'height' => $bas - $haut - 2,
	);
	if ( $zone['width'] < $largeur * 0.15 || $zone['height'] < $hauteur * 0.5 ) {
		return false;
	}
	$couverture = imagecrop( $image, $zone );
	if ( ! $couverture ) {
		return false;
	}
	switch ( $infos[2] ) {
		case IMAGETYPE_WEBP:
			$ok = function_exists( 'imagewebp' ) && imagewebp( $couverture, $fichier, 88 );
			break;
		case IMAGETYPE_PNG:
			$ok = imagepng( $couverture, $fichier );
			break;
		case IMAGETYPE_JPEG:
			$ok = imagejpeg( $couverture, $fichier, 90 );
			break;
		default:
			$ok = false;
	}
	return (bool) $ok;
}

/**
 * Recadre les couvertures Calaméo déjà importées (une seule fois par image) et refait leurs miniatures.
 *
 * @return array Bilan : nombre d'images recadrées, ignorées, et détail.
 */
function vf_deploi_recadrer_couvertures_existantes() {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	global $wpdb;
	$ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- action ponctuelle d'administration.
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE p.post_type = 'attachment' AND m.meta_key = 'vf_source' AND m.meta_value LIKE %s ORDER BY p.ID",
			$wpdb->esc_like( 'https://www.calameo.com/books/social/cover/' ) . '%'
		)
	);
	$bilan = array( 'recadrees' => 0, 'ignorees' => 0, 'detail' => array() );
	foreach ( $ids as $id ) {
		$id      = (int) $id;
		$fichier = get_attached_file( $id );
		if ( get_post_meta( $id, 'vf_recadree', true ) || ! $fichier || ! file_exists( $fichier ) ) {
			++$bilan['ignorees'];
			continue;
		}
		$anciennes = wp_get_attachment_metadata( $id );
		if ( ! vf_deploi_recadrer_couverture( $fichier ) ) {
			++$bilan['ignorees'];
			$bilan['detail'][] = array( 'id' => $id, 'recadree' => false );
			continue;
		}
		// Les anciennes miniatures (au format paysage) ne servent plus.
		if ( ! empty( $anciennes['sizes'] ) ) {
			foreach ( $anciennes['sizes'] as $taille ) {
				if ( ! empty( $taille['file'] ) ) {
					wp_delete_file( trailingslashit( dirname( $fichier ) ) . wp_basename( $taille['file'] ) );
				}
			}
		}
		$nouvelles = wp_generate_attachment_metadata( $id, $fichier );
		wp_update_attachment_metadata( $id, $nouvelles );
		update_post_meta( $id, 'vf_recadree', 1 );
		++$bilan['recadrees'];
		$bilan['detail'][] = array( 'id' => $id, 'recadree' => true, 'largeur' => (int) ( $nouvelles['width'] ?? 0 ), 'hauteur' => (int) ( $nouvelles['height'] ?? 0 ) );
	}
	return $bilan;
}

require_once __DIR__ . '/migration.php';

/**
 * Points d'accès (API REST de WordPress, session administrateur + jeton anti-falsification).
 */
add_action(
	'rest_api_init',
	function () {
		$droits = 'vf_deploi_autorise';
		register_rest_route(
			'vf-deploiement/v1',
			'/etat',
			array(
				'methods'             => 'GET',
				'permission_callback' => $droits,
				'callback'            => function () {
					return rest_ensure_response( vf_deploi_etat() );
				},
			)
		);
		register_rest_route(
			'vf-deploiement/v1',
			'/installer',
			array(
				'methods'             => 'POST',
				'permission_callback' => $droits,
				'args'                => array(
					'element' => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array_keys( vf_deploi_elements() ),
					),
				),
				'callback'            => function ( WP_REST_Request $r ) {
					return rest_ensure_response( vf_deploi_installer( $r['element'] ) );
				},
			)
		);
		register_rest_route(
			'vf-deploiement/v1',
			'/migration',
			array(
				'methods'             => 'POST',
				'permission_callback' => $droits,
				'args'                => array(
					'etape' => array( 'required' => true, 'type' => 'string', 'enum' => array( 'pages', 'finaliser', 'actualites', 'evenements' ) ),
					'debut' => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0 ),
				),
				'callback'            => function ( WP_REST_Request $r ) {
					switch ( $r['etape'] ) {
						case 'pages':
							return rest_ensure_response( vf_deploi_migrer_pages( (int) $r['debut'] ) );
						case 'actualites':
						case 'evenements':
							return rest_ensure_response( vf_deploi_migrer_contenus( $r['etape'], (int) $r['debut'] ) );
						default:
							return rest_ensure_response( vf_deploi_finaliser_migration() );
					}
				},
			)
		);
		register_rest_route(
			'vf-deploiement/v1',
			'/couvertures',
			array(
				'methods'             => 'POST',
				'permission_callback' => $droits,
				'callback'            => function () {
					return rest_ensure_response( vf_deploi_recadrer_couvertures_existantes() );
				},
			)
		);
		register_rest_route(
			'vf-deploiement/v1',
			'/image',
			array(
				'methods'             => 'POST',
				'permission_callback' => function () {
					return current_user_can( 'upload_files' ) && current_user_can( 'edit_others_posts' );
				},
				'args'                => array(
					'url' => array( 'required' => true, 'type' => 'string', 'format' => 'uri' ),
					'alt' => array( 'type' => 'string', 'default' => '' ),
				),
				'callback'            => function ( WP_REST_Request $r ) {
					return rest_ensure_response( vf_deploi_importer_image( $r['url'], $r['alt'] ) );
				},
			)
		);
	}
);

/**
 * Écran Outils > Déploiement Villefontaine.
 */
add_action(
	'admin_menu',
	function () {
		add_management_page( 'Déploiement Villefontaine', 'Déploiement Villefontaine', 'manage_options', 'vf-deploiement', 'vf_deploi_page' );
	}
);

/**
 * Affiche l'écran (les actions passent par l'API REST, avec le jeton de la session).
 */
function vf_deploi_page() {
	if ( ! vf_deploi_autorise() ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Déploiement Villefontaine</h1>
		<p>Les fichiers sont pris dans le dépôt <a href="<?php echo esc_url( 'https://github.com/' . VF_DEPLOI_DEPOT ); ?>" target="_blank" rel="noopener"><?php echo esc_html( VF_DEPLOI_DEPOT ); ?></a> (dossier <code>dist</code>). Chaque mise à jour remplace l’élément par la dernière version publiée.</p>
		<p><strong>Rappel :</strong> testez toujours sur la préproduction avant la production.</p>
		<div id="vf-deploi" aria-live="polite"><p>Chargement de l’état…</p></div>
	</div>
	<script>
	(function () {
		var nonce = <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;
		var racine = <?php echo wp_json_encode( esc_url_raw( rest_url( 'vf-deploiement/v1/' ) ) ); ?>;
		var zone = document.getElementById('vf-deploi');
		function appel(chemin, corps) {
			return fetch(racine + chemin, { method: corps ? 'POST' : 'GET', credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' }, body: corps ? JSON.stringify(corps) : undefined }).then(function (r) { return r.json(); });
		}
		function texte(t) { var s = document.createElement('span'); s.textContent = t; return s; }
		function afficher() {
			appel('etat').then(function (d) {
				zone.textContent = '';
				if (!d.elements) { zone.appendChild(texte(d.message || 'Erreur.')); return; }
				var p = document.createElement('p'); p.textContent = 'Dernière version du dépôt : ' + d.commit.slice(0, 7); zone.appendChild(p);
				var t = document.createElement('table'); t.className = 'widefat striped'; t.style.maxWidth = '900px';
				t.innerHTML = '<thead><tr><th scope="col">Élément</th><th scope="col">Installé</th><th scope="col">Disponible</th><th scope="col">Action</th></tr></thead>';
				var tb = document.createElement('tbody'); t.appendChild(tb);
				Object.keys(d.elements).forEach(function (slug) {
					var e = d.elements[slug], tr = document.createElement('tr');
					[e.libelle, (e.installee || '—') + (e.active ? ' (actif)' : ''), e.disponible || '—'].forEach(function (v) { var td = document.createElement('td'); td.textContent = v; tr.appendChild(td); });
					var td = document.createElement('td');
					if (e.disponible) {
						var b = document.createElement('button'); b.type = 'button'; b.className = 'button' + (e.installee !== e.disponible ? ' button-primary' : '');
						b.textContent = !e.installee ? 'Installer' : (e.installee !== e.disponible ? 'Mettre à jour' : 'Réinstaller');
						b.setAttribute('data-element', slug);
						b.addEventListener('click', function () {
							b.disabled = true; b.textContent = 'En cours…';
							appel('installer', { element: slug }).then(function (r) {
								alertZone(r.apres ? e.libelle + ' : ' + (r.avant || 'non installé') + ' → ' + r.apres : e.libelle + ' : ' + (r.message || 'échec'));
								afficher();
							});
						});
						td.appendChild(b);
					}
					tr.appendChild(td); tb.appendChild(tr);
				});
				zone.appendChild(t);
			});
		}
		function alertZone(m) { var n = document.createElement('div'); n.className = 'notice notice-info'; n.appendChild(texte(m)); zone.parentNode.insertBefore(n, zone); }
		afficher();
	})();
	</script>
	<?php
}
