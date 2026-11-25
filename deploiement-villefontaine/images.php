<?php
/**
 * Remise à la règle des images déjà présentes dans la médiathèque.
 *
 * La règle du site (extension Socle, includes/images.php) ne s'applique qu'aux nouveaux envois.
 * Cet outil l'applique aux images plus anciennes :
 * - une image plus large que la limite est réduite, sans changer de nom ni d'adresse (les pages qui l'affichent
 *   n'ont pas à être modifiées, et ses miniatures restent valables) ;
 * - un fichier d'origine plus lourd que la limite est supprimé.
 *
 * Les deux opérations sont définitives : l'outil travaille d'abord en simulation (il liste sans rien toucher),
 * puis par petits lots pour ne pas dépasser le temps accordé par le serveur à une requête.
 *
 * @package DeploiementVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Applique la règle des images à la médiathèque.
 *
 * @param bool $simulation Vrai : ne rien modifier, seulement lister.
 * @param int  $lot        Nombre maximal d'images traitées par appel.
 * @return array Bilan.
 */
function vf_deploi_repasser_images( $simulation = true, $lot = 12 ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$largeur_max = defined( 'VF_SOCLE_IMAGE_LARGEUR_MAX' ) ? (int) VF_SOCLE_IMAGE_LARGEUR_MAX : 2400;
	$poids_max   = defined( 'VF_SOCLE_ORIGINAL_POIDS_MAX' ) ? (int) VF_SOCLE_ORIGINAL_POIDS_MAX : 2 * MB_IN_BYTES;
	$lot         = max( 1, min( 40, (int) $lot ) );

	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	$bilan = array(
		'simulation'            => (bool) $simulation,
		'largeur_max'           => $largeur_max,
		'images'                => count( $ids ),
		'a_reduire'             => array(),
		'originaux_a_supprimer' => array(),
		'reduites'              => array(),
		'originaux_supprimes'   => array(),
		'erreurs'               => array(),
	);
	$faits = 0;

	foreach ( $ids as $id ) {
		$fichier = get_attached_file( $id );
		$meta    = wp_get_attachment_metadata( $id );
		if ( ! $fichier || ! is_array( $meta ) || ! is_file( $fichier ) ) {
			continue;
		}
		$type = wp_check_filetype( $fichier );
		if ( ! in_array( $type['type'], array( 'image/webp', 'image/jpeg', 'image/png' ), true ) ) {
			continue; // Les SVG et les GIF animés ne se réduisent pas ainsi.
		}
		$nom = wp_basename( $fichier );

		// 1. Fichier d'origine trop lourd.
		if ( ! empty( $meta['original_image'] ) ) {
			$original = path_join( dirname( $fichier ), wp_basename( $meta['original_image'] ) );
			if ( $original !== $fichier && is_file( $original ) && filesize( $original ) > $poids_max ) {
				$ligne = wp_basename( $original ) . ' (' . size_format( filesize( $original ), 1 ) . ')';
				if ( $simulation || $faits >= $lot ) {
					$bilan['originaux_a_supprimer'][] = $ligne;
				} else {
					wp_delete_file( $original );
					if ( file_exists( $original ) ) {
						$bilan['erreurs'][] = $ligne . ' : suppression impossible';
					} else {
						unset( $meta['original_image'] );
						wp_update_attachment_metadata( $id, $meta );
						$bilan['originaux_supprimes'][] = $ligne;
					}
					++$faits;
				}
			}
		}

		// 2. Image trop large.
		$largeur = (int) ( $meta['width'] ?? 0 );
		if ( $largeur > $largeur_max ) {
			$ligne = $nom . ' (' . $largeur . '×' . (int) ( $meta['height'] ?? 0 ) . ', ' . size_format( filesize( $fichier ), 0 ) . ')';
			if ( $simulation || $faits >= $lot ) {
				$bilan['a_reduire'][] = $ligne;
				continue;
			}
			++$faits;
			$editeur = wp_get_image_editor( $fichier );
			if ( is_wp_error( $editeur ) ) {
				$bilan['erreurs'][] = $nom . ' : ' . $editeur->get_error_message();
				continue;
			}
			$reduit = $editeur->resize( $largeur_max, null, false );
			// Même nom, même format : l'adresse de l'image ne change pas.
			$enregistre = is_wp_error( $reduit ) ? $reduit : $editeur->save( $fichier, $type['type'] );
			if ( is_wp_error( $enregistre ) || empty( $enregistre['path'] ) || wp_normalize_path( $enregistre['path'] ) !== wp_normalize_path( $fichier ) ) {
				$bilan['erreurs'][] = $nom . ' : ' . ( is_wp_error( $enregistre ) ? $enregistre->get_error_message() : 'enregistrement à un autre emplacement' );
				continue;
			}
			clearstatcache( true, $fichier );
			$meta['width']    = (int) $enregistre['width'];
			$meta['height']   = (int) $enregistre['height'];
			$meta['filesize'] = (int) filesize( $fichier );
			wp_update_attachment_metadata( $id, $meta );
			$bilan['reduites'][] = $nom . ' : ' . $largeur . ' → ' . $meta['width'] . '×' . $meta['height'] . ', ' . size_format( $meta['filesize'], 0 );
		}
	}

	$bilan['reste'] = count( $bilan['a_reduire'] ) + count( $bilan['originaux_a_supprimer'] );
	return $bilan;
}
