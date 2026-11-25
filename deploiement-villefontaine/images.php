<?php
/**
 * Remise à la règle des images déjà présentes dans la médiathèque.
 *
 * La règle du site (extension Socle, includes/images.php) ne s'applique qu'aux nouveaux envois.
 * Cet outil l'applique aux images plus anciennes :
 * - une image plus large que la limite est réduite, sans changer de nom ni d'adresse (les pages qui l'affichent
 *   n'ont pas à être modifiées, et ses miniatures restent valables) ;
 * - une image qui avait été réduite plus que la règle ne le demande (envoyée quand la limite était plus basse)
 *   est refaite à partir de son fichier d'origine, s'il existe encore ;
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
 * @param int  $seule      Identifiant d'une image : ne traiter que celle-ci (0 = toute la médiathèque).
 * @return array Bilan.
 */
function vf_deploi_repasser_images( $simulation = true, $lot = 12, $seule = 0 ) {
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

	if ( $seule ) {
		$ids = array_values( array_intersect( array_map( 'intval', $ids ), array( (int) $seule ) ) );
	}

	$bilan = array(
		'simulation'            => (bool) $simulation,
		'largeur_max'           => $largeur_max,
		'images'                => count( $ids ),
		'a_refaire'             => array(),
		'a_reduire'             => array(),
		'originaux_a_supprimer' => array(),
		'refaites'              => array(),
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
		$nom     = wp_basename( $fichier );
		$largeur = (int) ( $meta['width'] ?? 0 );

		// Fichier d'origine (ce que l'agent a envoyé), s'il est encore sur le serveur.
		$original       = '';
		$taille_origine = false;
		if ( ! empty( $meta['original_image'] ) ) {
			$chemin = path_join( dirname( $fichier ), wp_basename( $meta['original_image'] ) );
			if ( $chemin !== $fichier && is_file( $chemin ) ) {
				$original       = $chemin;
				$taille_origine = wp_getimagesize( $chemin );
			}
		}

		// Ce qu'il y a à faire pour cette image.
		$cible     = $taille_origine ? min( $largeur_max, (int) $taille_origine[0] ) : 0;
		$refaire   = $original && $largeur > 0 && $cible > $largeur + 1; // Plus étroite que ce que la règle autorise.
		$reduire   = $largeur > $largeur_max;
		$supprimer = $original && filesize( $original ) > $poids_max;
		if ( ! $refaire && ! $reduire && ! $supprimer ) {
			continue;
		}
		$ligne_image    = $nom . ' (' . $largeur . '×' . (int) ( $meta['height'] ?? 0 ) . ', ' . size_format( filesize( $fichier ), 0 ) . ')';
		$ligne_original = $original ? wp_basename( $original ) . ' (' . size_format( filesize( $original ), 1 ) . ')' : '';

		if ( $simulation || $faits >= $lot ) {
			if ( $refaire ) {
				$bilan['a_refaire'][] = $ligne_image . ' → ' . $cible . ' px depuis ' . wp_basename( $original );
			}
			if ( $reduire ) {
				$bilan['a_reduire'][] = $ligne_image;
			}
			if ( $supprimer ) {
				$bilan['originaux_a_supprimer'][] = $ligne_original;
			}
			continue;
		}
		++$faits;

		// 1. Refaire depuis l'original, ou réduire sur place. Même nom, même format : l'adresse de l'image ne change pas.
		if ( $refaire || $reduire ) {
			$editeur = wp_get_image_editor( $refaire ? $original : $fichier );
			if ( is_wp_error( $editeur ) ) {
				$bilan['erreurs'][] = $nom . ' : ' . $editeur->get_error_message();
				continue;
			}
			$dimensions = $editeur->get_size();
			$resultat   = ( (int) $dimensions['width'] > $largeur_max ) ? $editeur->resize( $largeur_max, null, false ) : true;
			$enregistre = is_wp_error( $resultat ) ? $resultat : $editeur->save( $fichier, $type['type'] );
			if ( is_wp_error( $enregistre ) || empty( $enregistre['path'] ) || wp_normalize_path( $enregistre['path'] ) !== wp_normalize_path( $fichier ) ) {
				$bilan['erreurs'][] = $nom . ' : ' . ( is_wp_error( $enregistre ) ? $enregistre->get_error_message() : 'enregistrement à un autre emplacement' );
				continue; // L'original n'est pas supprimé tant que l'image n'a pas été refaite.
			}
			clearstatcache( true, $fichier );
			$meta['width']    = (int) $enregistre['width'];
			$meta['height']   = (int) $enregistre['height'];
			$meta['filesize'] = (int) filesize( $fichier );
			wp_update_attachment_metadata( $id, $meta );
			if ( $refaire ) {
				// L'image est plus grande qu'avant : les tailles intermédiaires qui manquaient sont créées.
				$complet = wp_update_image_subsizes( $id );
				$meta    = is_array( $complet ) ? $complet : wp_get_attachment_metadata( $id );
			}
			$bilan[ $refaire ? 'refaites' : 'reduites' ][] = $nom . ' : ' . $largeur . ' → ' . $meta['width'] . '×' . $meta['height'] . ', ' . size_format( (int) filesize( $fichier ), 0 );
		}

		// 2. Fichier d'origine trop lourd.
		if ( $supprimer ) {
			wp_delete_file( $original );
			if ( file_exists( $original ) ) {
				$bilan['erreurs'][] = $ligne_original . ' : suppression impossible';
			} else {
				unset( $meta['original_image'] );
				wp_update_attachment_metadata( $id, $meta );
				$bilan['originaux_supprimes'][] = $ligne_original;
			}
		}
	}

	$bilan['reste'] = count( $bilan['a_refaire'] ) + count( $bilan['a_reduire'] ) + count( $bilan['originaux_a_supprimer'] );
	return $bilan;
}
