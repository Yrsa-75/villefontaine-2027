<?php
/**
 * Import (Excel .xlsx ou CSV) et export (CSV lisible par Excel) des lieux.
 * Écran : Annuaire > Importer / exporter.
 *
 * @package AnnuaireVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Colonnes du fichier, dans l'ordre de l'export.
 *
 * @return array colonne => champ (meta, ou mot réservé).
 */
function vf_annuaire_colonnes() {
	return array(
		'id'          => 'ID',
		'nom'         => 'post_title',
		'categorie'   => 'categorie',
		'adresse'     => 'vf_adresse',
		'code_postal' => 'vf_code_postal',
		'ville'       => 'vf_ville',
		'latitude'    => 'vf_lat',
		'longitude'   => 'vf_lng',
		'telephone'   => 'vf_tel',
		'courriel'    => 'vf_courriel',
		'site'        => 'vf_site',
		'horaires'    => 'vf_horaires',
		'pmr'         => 'vf_pmr',
		'description' => 'post_excerpt',
	);
}

/**
 * Ajoute l'écran sous « Annuaire ».
 */
function vf_annuaire_menu_import() {
	add_submenu_page( 'edit.php?post_type=vf_lieu', 'Importer / exporter les lieux', 'Importer / exporter', 'publish_posts', 'vf-annuaire-import', 'vf_annuaire_page_import' );
}
add_action( 'admin_menu', 'vf_annuaire_menu_import' );

/**
 * Écran d'import / export.
 */
function vf_annuaire_page_import() {
	if ( ! current_user_can( 'publish_posts' ) ) {
		return;
	}
	$bilan = get_transient( 'vf_annuaire_bilan_' . get_current_user_id() );
	delete_transient( 'vf_annuaire_bilan_' . get_current_user_id() );
	?>
	<div class="wrap">
		<h1>Importer / exporter les lieux</h1>
		<?php if ( is_array( $bilan ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $bilan['type'] ); ?>"><p><?php echo esc_html( $bilan['message'] ); ?></p>
			<?php if ( ! empty( $bilan['erreurs'] ) ) : ?><ul style="list-style:disc;padding-left:20px"><?php foreach ( array_slice( $bilan['erreurs'], 0, 30 ) as $e ) : ?><li><?php echo esc_html( $e ); ?></li><?php endforeach; ?></ul><?php endif; ?>
			</div>
		<?php endif; ?>

		<h2>Exporter</h2>
		<p>Télécharge tous les lieux dans un fichier que vous pouvez ouvrir et modifier dans Excel, puis réimporter.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'vf_annuaire_export' ); ?>
			<input type="hidden" name="action" value="vf_annuaire_export">
			<button class="button button-secondary">Télécharger les lieux (.csv)</button>
		</form>

		<h2 style="margin-top:32px">Importer</h2>
		<p>Fichier Excel (.xlsx) ou CSV. La première ligne doit contenir les noms de colonnes :</p>
		<p><code><?php echo esc_html( implode( ' ; ', array_keys( vf_annuaire_colonnes() ) ) ); ?></code></p>
		<ul style="list-style:disc;padding-left:20px;max-width:900px">
			<li>Seule la colonne <strong>nom</strong> est obligatoire. Les colonnes absentes sont ignorées.</li>
			<li>Avec une colonne <strong>id</strong> remplie (issue d’un export), le lieu existant est <strong>mis à jour</strong> ; sans id, un nouveau lieu est créé.</li>
			<li>Les catégories qui n’existent pas encore sont créées automatiquement.</li>
			<li>Sans latitude ni longitude, le lieu est créé mais n’apparaît pas sur la carte.</li>
		</ul>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'vf_annuaire_import' ); ?>
			<input type="hidden" name="action" value="vf_annuaire_import">
			<p><label for="vf-fichier"><strong>Fichier à importer</strong></label><br><input type="file" id="vf-fichier" name="fichier" accept=".xlsx,.csv" required></p>
			<p><label><input type="checkbox" name="brouillon" value="1"> Importer en brouillon (pour vérifier avant publication)</label></p>
			<button class="button button-primary">Importer</button>
		</form>
	</div>
	<?php
}

/**
 * Export CSV (séparateur point-virgule, encodage UTF-8 avec BOM : s'ouvre tel quel dans Excel).
 */
function vf_annuaire_export() {
	if ( ! current_user_can( 'publish_posts' ) ) {
		wp_die( 'Droits insuffisants.', 403 );
	}
	check_admin_referer( 'vf_annuaire_export' );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="lieux-villefontaine-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$sortie = fopen( 'php://output', 'w' );
	fwrite( $sortie, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $sortie, array_keys( vf_annuaire_colonnes() ), ';' );
	$lieux = get_posts( array( 'post_type' => 'vf_lieu', 'post_status' => array( 'publish', 'draft', 'pending' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	foreach ( $lieux as $lieu ) {
		$ligne = array();
		foreach ( vf_annuaire_colonnes() as $champ ) {
			if ( 'ID' === $champ ) {
				$ligne[] = $lieu->ID;
			} elseif ( 'post_title' === $champ || 'post_excerpt' === $champ ) {
				$ligne[] = vf_annuaire_cellule_sure( $lieu->$champ );
			} elseif ( 'categorie' === $champ ) {
				$t       = get_the_terms( $lieu, 'vf_categorie_lieu' );
				$ligne[] = ( $t && ! is_wp_error( $t ) ) ? vf_annuaire_cellule_sure( $t[0]->name ) : '';
			} else {
				$ligne[] = vf_annuaire_cellule_sure( (string) get_post_meta( $lieu->ID, $champ, true ) );
			}
		}
		fputcsv( $sortie, $ligne, ';' );
	}
	fclose( $sortie ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_vf_annuaire_export', 'vf_annuaire_export' );

/**
 * Protège une cellule contre l'exécution de formules à l'ouverture dans Excel (injection CSV).
 *
 * @param string $v Valeur.
 * @return string
 */
function vf_annuaire_cellule_sure( $v ) {
	$v = (string) $v;
	return ( '' !== $v && in_array( $v[0], array( '=', '+', '-', '@' ), true ) && ! is_numeric( $v ) ) ? "'" . $v : $v;
}

/**
 * Lit un fichier .xlsx (première feuille) sans bibliothèque externe.
 *
 * @param string $chemin Fichier.
 * @return array|WP_Error Lignes (tableaux de cellules).
 */
function vf_annuaire_lire_xlsx( $chemin ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'zip', 'Le serveur ne sait pas lire les fichiers Excel : enregistrez le fichier au format CSV.' );
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $chemin ) ) {
		return new WP_Error( 'zip', 'Fichier Excel illisible.' );
	}
	$partages = array();
	$xml_s    = $zip->getFromName( 'xl/sharedStrings.xml' );
	if ( $xml_s ) {
		$s = simplexml_load_string( $xml_s, 'SimpleXMLElement', LIBXML_NONET );
		foreach ( $s->si as $si ) {
			$partages[] = isset( $si->t ) ? (string) $si->t : implode( '', array_map( 'strval', $si->xpath( './/*[local-name()="t"]' ) ) );
		}
	}
	$xml_f = $zip->getFromName( 'xl/worksheets/sheet1.xml' );
	$zip->close();
	if ( ! $xml_f ) {
		return new WP_Error( 'feuille', 'Première feuille introuvable dans le fichier Excel.' );
	}
	$f      = simplexml_load_string( $xml_f, 'SimpleXMLElement', LIBXML_NONET );
	$lignes = array();
	foreach ( $f->sheetData->row as $row ) {
		$ligne = array();
		foreach ( $row->c as $c ) {
			$ref   = (string) $c['r'];
			$col   = 0;
			foreach ( str_split( preg_replace( '/\d/', '', $ref ) ) as $lettre ) {
				$col = $col * 26 + ( ord( $lettre ) - 64 );
			}
			$type = (string) $c['t'];
			if ( 's' === $type ) {
				$val = $partages[ (int) $c->v ] ?? '';
			} elseif ( 'inlineStr' === $type ) {
				$val = (string) $c->is->t;
			} else {
				$val = (string) $c->v;
			}
			$ligne[ $col - 1 ] = $val;
		}
		if ( $ligne ) {
			$max     = max( array_keys( $ligne ) );
			$lignes[] = array_replace( array_fill( 0, $max + 1, '' ), $ligne );
		}
	}
	return $lignes;
}

/**
 * Lit un fichier CSV (séparateur ; , ou tabulation détecté automatiquement).
 *
 * @param string $chemin Fichier.
 * @return array
 */
function vf_annuaire_lire_csv( $chemin ) {
	$texte = (string) file_get_contents( $chemin ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$texte = preg_replace( '/^\xEF\xBB\xBF/', '', $texte );
	if ( ! mb_check_encoding( $texte, 'UTF-8' ) ) {
		$texte = mb_convert_encoding( $texte, 'UTF-8', 'Windows-1252' );
	}
	$premiere = strtok( $texte, "\n" );
	$sep      = ';';
	foreach ( array( "\t", ',', ';' ) as $candidat ) {
		if ( substr_count( (string) $premiere, $candidat ) > substr_count( (string) $premiere, $sep ) ) {
			$sep = $candidat;
		}
	}
	$flux = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fwrite( $flux, $texte ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	rewind( $flux );
	$lignes = array();
	while ( ( $l = fgetcsv( $flux, 0, $sep ) ) !== false ) {
		$lignes[] = $l;
	}
	fclose( $flux ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return $lignes;
}

/**
 * Import.
 */
function vf_annuaire_import() {
	if ( ! current_user_can( 'publish_posts' ) ) {
		wp_die( 'Droits insuffisants.', 403 );
	}
	check_admin_referer( 'vf_annuaire_import' );
	$retour = admin_url( 'edit.php?post_type=vf_lieu&page=vf-annuaire-import' );
	$bilan  = function ( $type, $message, $erreurs = array() ) use ( $retour ) {
		set_transient( 'vf_annuaire_bilan_' . get_current_user_id(), compact( 'type', 'message', 'erreurs' ), 300 );
		wp_safe_redirect( $retour );
		exit;
	};

	if ( empty( $_FILES['fichier']['tmp_name'] ) || ! is_uploaded_file( $_FILES['fichier']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$bilan( 'error', 'Aucun fichier reçu.' );
	}
	if ( (int) $_FILES['fichier']['size'] > 5 * MB_IN_BYTES ) {
		$bilan( 'error', 'Fichier trop volumineux (5 Mo maximum).' );
	}
	$nom  = sanitize_file_name( wp_unslash( $_FILES['fichier']['name'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$ext  = strtolower( pathinfo( $nom, PATHINFO_EXTENSION ) );
	$tmp  = $_FILES['fichier']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( 'xlsx' === $ext ) {
		$lignes = vf_annuaire_lire_xlsx( $tmp );
	} elseif ( 'csv' === $ext ) {
		$lignes = vf_annuaire_lire_csv( $tmp );
	} else {
		$bilan( 'error', 'Format non accepté : choisissez un fichier .xlsx ou .csv.' );
	}
	if ( is_wp_error( $lignes ) ) {
		$bilan( 'error', $lignes->get_error_message() );
	}
	if ( count( $lignes ) < 2 ) {
		$bilan( 'error', 'Le fichier ne contient aucune ligne de données.' );
	}

	// Correspondance des en-têtes (sans accents ni majuscules : « Code postal » = « code_postal »).
	$entetes = array();
	foreach ( array_shift( $lignes ) as $i => $e ) {
		$cle = str_replace( array( ' ', '-' ), '_', strtolower( remove_accents( trim( (string) $e ) ) ) );
		$cle = array( 'lat' => 'latitude', 'lng' => 'longitude', 'lon' => 'longitude', 'tel' => 'telephone', 'email' => 'courriel', 'mail' => 'courriel', 'cp' => 'code_postal', 'titre' => 'nom', 'categorie_' => 'categorie' )[ $cle ] ?? $cle;
		if ( isset( vf_annuaire_colonnes()[ $cle ] ) ) {
			$entetes[ $i ] = $cle;
		}
	}
	if ( ! in_array( 'nom', $entetes, true ) ) {
		$bilan( 'error', 'Colonne « nom » introuvable sur la première ligne du fichier.' );
	}

	$statut  = empty( $_POST['brouillon'] ) ? 'publish' : 'draft';
	$crees   = 0;
	$majs    = 0;
	$erreurs = array();
	foreach ( $lignes as $n => $ligne ) {
		$d = array();
		foreach ( $entetes as $i => $cle ) {
			$d[ $cle ] = isset( $ligne[ $i ] ) ? trim( (string) $ligne[ $i ] ) : '';
		}
		if ( '' === ( $d['nom'] ?? '' ) ) {
			continue; // Ligne vide.
		}
		$id = isset( $d['id'] ) ? absint( $d['id'] ) : 0;
		if ( $id && ( 'vf_lieu' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) ) {
			$erreurs[] = 'Ligne ' . ( $n + 2 ) . ' : l’identifiant ' . $id . ' ne correspond à aucun lieu modifiable, ligne ignorée.';
			continue;
		}
		$post = array(
			'post_type'   => 'vf_lieu',
			'post_title'  => sanitize_text_field( $d['nom'] ),
			'post_status' => $id ? get_post_status( $id ) : $statut,
		);
		if ( isset( $d['description'] ) ) {
			$post['post_excerpt'] = sanitize_textarea_field( $d['description'] );
		}
		if ( $id ) {
			$post['ID'] = $id;
			$res        = wp_update_post( $post, true );
		} else {
			$res = wp_insert_post( $post, true );
		}
		if ( is_wp_error( $res ) ) {
			$erreurs[] = 'Ligne ' . ( $n + 2 ) . ' : ' . $res->get_error_message();
			continue;
		}
		foreach ( vf_annuaire_colonnes() as $col => $champ ) {
			if ( isset( $d[ $col ] ) && 0 === strpos( $champ, 'vf_' ) ) {
				update_post_meta( $res, $champ, vf_annuaire_nettoie( $champ, $d[ $col ] ) );
			}
		}
		if ( ! empty( $d['categorie'] ) ) {
			$cat   = sanitize_text_field( $d['categorie'] );
			$terme = term_exists( $cat, 'vf_categorie_lieu' );
			if ( ! $terme ) {
				$terme = wp_insert_term( $cat, 'vf_categorie_lieu' );
				if ( ! is_wp_error( $terme ) ) {
					$couleurs = array_keys( vf_annuaire_couleurs() );
					update_term_meta( $terme['term_id'], 'vf_couleur', $couleurs[ wp_count_terms( array( 'taxonomy' => 'vf_categorie_lieu', 'hide_empty' => false ) ) % count( $couleurs ) ] );
				}
			}
			if ( ! is_wp_error( $terme ) ) {
				wp_set_object_terms( $res, (int) $terme['term_id'], 'vf_categorie_lieu' );
			}
		}
		if ( $id ) {
			++$majs;
		} else {
			++$crees;
		}
	}
	$bilan( $erreurs ? 'warning' : 'success', sprintf( 'Import terminé : %d lieu(x) créé(s), %d mis à jour.', $crees, $majs ), $erreurs );
}
add_action( 'admin_post_vf_annuaire_import', 'vf_annuaire_import' );
