<?php
/**
 * Images envoyées dans la médiathèque : une seule règle pour tout le site.
 *
 * - Toute photo JPEG ou PNG est convertie en WebP (plus léger à qualité égale), y compris ses miniatures.
 * - Aucune image ne dépasse 1 500 pixels de large : au-delà, elle est réduite à l'envoi.
 * - Le fichier d'origine reste sur le serveur (il n'est jamais montré aux visiteurs) pour pouvoir
 *   le récupérer en haute définition, sauf s'il pèse plus de 2 Mo : il est alors supprimé.
 *
 * Les agents n'ont rien à faire : ils envoient leur photo telle quelle.
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/** Largeur maximale d'une image du site, en pixels. */
const VF_SOCLE_IMAGE_LARGEUR_MAX = 1500;

/** Poids au-delà duquel le fichier d'origine n'est pas conservé, en octets (2 Mo). */
const VF_SOCLE_ORIGINAL_POIDS_MAX = 2097152;

/**
 * WordPress enregistre les images en WebP quand le serveur le permet.
 *
 * @param array $formats Correspondance type envoyé => type enregistré.
 * @return array
 */
function vf_socle_images_webp( $formats ) {
	if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png']  = 'image/webp';
	}
	return $formats;
}
add_filter( 'image_editor_output_format', 'vf_socle_images_webp' );

/**
 * Réduction à l'envoi : 1 500 pixels de large au plus.
 *
 * WordPress raisonne sur le plus grand côté de l'image. Pour une photo en hauteur (une affiche, par exemple),
 * on lui indique donc la hauteur qui correspond à 1 500 pixels de large : l'affiche garde sa finesse.
 *
 * @param int   $seuil   Seuil de WordPress (2 560 pixels).
 * @param array $taille  Largeur et hauteur de l'image envoyée.
 * @return int|false Seuil à appliquer, ou false pour ne pas réduire.
 */
function vf_socle_images_seuil( $seuil, $taille = array() ) {
	$largeur = (int) ( $taille[0] ?? 0 );
	$hauteur = (int) ( $taille[1] ?? 0 );
	if ( $largeur <= 0 || $hauteur <= 0 ) {
		return VF_SOCLE_IMAGE_LARGEUR_MAX;
	}
	if ( $largeur <= VF_SOCLE_IMAGE_LARGEUR_MAX ) {
		return false; // Déjà assez étroite : elle est seulement convertie en WebP.
	}
	return (int) max( VF_SOCLE_IMAGE_LARGEUR_MAX, floor( VF_SOCLE_IMAGE_LARGEUR_MAX * $hauteur / $largeur ) );
}
add_filter( 'big_image_size_threshold', 'vf_socle_images_seuil', 10, 2 );

/**
 * Les deux grandes tailles intermédiaires de WordPress (1 536 et 2 048 pixels) n'ont plus d'utilité
 * puisque rien ne dépasse 1 500 pixels : on évite de créer ces fichiers.
 *
 * @param array $tailles Tailles à générer.
 * @return array
 */
function vf_socle_images_tailles( $tailles ) {
	unset( $tailles['1536x1536'], $tailles['2048x2048'] );
	return $tailles;
}
add_filter( 'intermediate_image_sizes_advanced', 'vf_socle_images_tailles' );

/**
 * Après l'envoi : le fichier d'origine est supprimé s'il pèse plus de 2 Mo.
 * En dessous, il est conservé à côté de la version WebP (WordPress le supprimera avec l'image).
 *
 * @param array $meta Informations de l'image (dimensions, tailles générées, nom du fichier d'origine).
 * @param int   $id   Identifiant de l'image dans la médiathèque.
 * @return array
 */
function vf_socle_images_original( $meta, $id = 0 ) {
	if ( empty( $meta['original_image'] ) || ! is_array( $meta ) ) {
		return $meta;
	}
	$principal = get_attached_file( $id );
	if ( ! $principal ) {
		return $meta;
	}
	$original = path_join( dirname( $principal ), wp_basename( $meta['original_image'] ) );
	// Sécurité : on ne supprime jamais le fichier affiché sur le site, seulement sa source.
	if ( $original === $principal || ! is_file( $original ) || ! is_file( $principal ) ) {
		return $meta;
	}
	if ( filesize( $original ) > VF_SOCLE_ORIGINAL_POIDS_MAX ) {
		wp_delete_file( $original );
		if ( ! file_exists( $original ) ) {
			unset( $meta['original_image'] );
		}
	}
	return $meta;
}
add_filter( 'wp_generate_attachment_metadata', 'vf_socle_images_original', 20, 2 );

/**
 * Rappel de la règle sous la zone d'envoi de la médiathèque.
 */
function vf_socle_images_aide() {
	echo '<p class="vf-aide-images" style="max-width:46em;margin:12px auto 0">Envoyez vos photos telles quelles : elles sont converties automatiquement au format WebP et ramenées à ' . esc_html( number_format_i18n( VF_SOCLE_IMAGE_LARGEUR_MAX ) ) . ' pixels de large. Le fichier d’origine est conservé s’il pèse moins de 2 Mo.</p>';
}
add_action( 'post-upload-ui', 'vf_socle_images_aide' );
