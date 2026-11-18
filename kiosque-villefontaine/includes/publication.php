<?php
/**
 * Type de contenu « Publication » : couverture (image mise en avant), lien Calaméo, PDF.
 *
 * @package KiosqueVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Déclare le type « Publication » et la taxonomie « Type de publication ».
 */
function vf_kiosque_types() {
	register_post_type(
		'vf_publication',
		array(
			'labels'        => array(
				'name'               => 'Kiosque',
				'singular_name'      => 'Publication',
				'menu_name'          => 'Kiosque',
				'add_new'            => 'Ajouter une publication',
				'add_new_item'       => 'Ajouter une publication',
				'edit_item'          => 'Modifier la publication',
				'view_item'          => 'Voir la publication',
				'search_items'       => 'Rechercher une publication',
				'not_found'          => 'Aucune publication',
				'not_found_in_trash' => 'Aucune publication dans la corbeille',
				'all_items'          => 'Toutes les publications',
				'featured_image'     => 'Couverture',
				'set_featured_image' => 'Choisir la couverture',
			),
			'public'        => true,
			'has_archive'   => 'kiosque',
			'rewrite'       => array( 'slug' => 'kiosque', 'with_front' => false ),
			'menu_icon'     => 'dashicons-book-alt',
			'menu_position' => 8,
			'supports'      => array( 'title', 'thumbnail', 'excerpt', 'custom-fields' ),
			'show_in_rest'  => true,
		)
	);
	register_taxonomy(
		'vf_type_publication',
		'vf_publication',
		array(
			'labels'            => array(
				'name'          => 'Types de publication',
				'singular_name' => 'Type de publication',
				'menu_name'     => 'Types',
				'add_new_item'  => 'Ajouter un type (ex. Le Mag)',
			),
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'kiosque/type', 'with_front' => false ),
		)
	);
	foreach ( array( 'vf_calameo' => 'esc_url_raw', 'vf_pdf' => 'esc_url_raw' ) as $cle => $nettoyage ) {
		register_post_meta(
			'vf_publication',
			$cle,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => $nettoyage,
				'auth_callback'     => function ( $autorise, $c, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
add_action( 'init', 'vf_kiosque_types' );

/**
 * Code Calaméo extrait d'une adresse (calameo.com/read/CODE, /books/CODE ou ?bkcode=CODE).
 *
 * @param string $url Adresse saisie.
 * @return string Code (lettres et chiffres uniquement) ou chaîne vide.
 */
function vf_kiosque_code_calameo( $url ) {
	if ( preg_match( '#(?:/read/|/books/|bkcode=)([A-Za-z0-9]+)#', (string) $url, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * Encadré « Lecture en ligne et PDF ».
 */
function vf_kiosque_boite() {
	add_meta_box( 'vf-kiosque-liens', 'Lecture en ligne et PDF', 'vf_kiosque_boite_html', 'vf_publication', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'vf_kiosque_boite' );

/**
 * Formulaire de l'encadré.
 *
 * @param WP_Post $post Publication.
 */
function vf_kiosque_boite_html( $post ) {
	wp_nonce_field( 'vf_kiosque_enregistre', 'vf_kiosque_jeton' );
	$calameo = (string) get_post_meta( $post->ID, 'vf_calameo', true );
	$pdf     = (string) get_post_meta( $post->ID, 'vf_pdf', true );
	?>
	<p><label for="vf_calameo"><strong>Adresse Calaméo</strong></label><br>
	<input type="url" id="vf_calameo" name="vf_calameo" value="<?php echo esc_attr( $calameo ); ?>" style="width:100%" placeholder="https://www.calameo.com/read/…"><br>
	<span class="description">Copiez l’adresse de la publication sur calameo.com. Le lecteur ne se charge qu’après accord du visiteur (RGPD).</span>
	<?php if ( $calameo && ! vf_kiosque_code_calameo( $calameo ) ) : ?><br><strong style="color:#b32d2e">Adresse non reconnue : elle doit contenir /read/ suivi du code de la publication.</strong><?php endif; ?></p>
	<p><label for="vf_pdf"><strong>Fichier PDF</strong></label><br>
	<input type="url" id="vf_pdf" name="vf_pdf" value="<?php echo esc_attr( $pdf ); ?>" style="width:calc(100% - 180px)" placeholder="https://…/fichier.pdf">
	<button type="button" class="button" id="vf-choisir-pdf">Choisir dans la médiathèque</button><br>
	<span class="description">Facultatif, mais recommandé : le PDF reste lisible sans service externe.</span></p>
	<p class="description">La <strong>couverture</strong> est l’image mise en avant (colonne de droite), au format portrait.</p>
	<script>
	document.getElementById('vf-choisir-pdf').addEventListener('click', function () {
		if (!window.wp || !wp.media) { return; }
		var cadre = wp.media({ title: 'Choisir le PDF', library: { type: 'application/pdf' }, multiple: false, button: { text: 'Utiliser ce PDF' } });
		cadre.on('select', function () { document.getElementById('vf_pdf').value = cadre.state().get('selection').first().get('url'); });
		cadre.open();
	});
	</script>
	<?php
}

/**
 * La médiathèque est nécessaire au bouton « Choisir dans la médiathèque ».
 *
 * @param string $page Écran.
 */
function vf_kiosque_admin_scripts( $page ) {
	if ( in_array( $page, array( 'post.php', 'post-new.php' ), true ) && 'vf_publication' === get_current_screen()->post_type ) {
		wp_enqueue_media();
	}
}
add_action( 'admin_enqueue_scripts', 'vf_kiosque_admin_scripts' );

/**
 * Enregistre l'encadré.
 *
 * @param int $post_id Publication.
 */
function vf_kiosque_enregistre( $post_id ) {
	if ( ! isset( $_POST['vf_kiosque_jeton'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vf_kiosque_jeton'] ) ), 'vf_kiosque_enregistre' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( 'vf_calameo', 'vf_pdf' ) as $cle ) {
		if ( isset( $_POST[ $cle ] ) ) {
			update_post_meta( $post_id, $cle, esc_url_raw( wp_unslash( $_POST[ $cle ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}
	}
}
add_action( 'save_post_vf_publication', 'vf_kiosque_enregistre' );

/**
 * Gabarits : page Kiosque (toutes les publications) et page d'une publication.
 */
function vf_kiosque_gabarits() {
	if ( ! function_exists( 'register_block_template' ) ) {
		return;
	}
	$entete = '<!-- wp:template-part {"slug":"header","tagName":"div","className":"vf-zone-entete"} /-->';
	$pied   = '<!-- wp:template-part {"slug":"footer","tagName":"div","className":"vf-zone-pied"} /-->';
	$page   = function ( $titre, $bloc ) use ( $entete, $pied ) {
		return $entete . '
<!-- wp:group {"tagName":"main","anchor":"contenu","className":"vf-liste","layout":{"type":"default"}} -->
<main class="wp-block-group vf-liste" id="contenu"><!-- wp:group {"className":"conteneur","layout":{"type":"default"}} -->
<div class="wp-block-group conteneur"><!-- wp:vf/fil-ariane /-->
' . $titre . '
' . $bloc . '</div>
<!-- /wp:group --></main>
<!-- /wp:group -->
' . $pied;
	};
	register_block_template(
		'kiosque-villefontaine//archive-vf_publication',
		array(
			'title'      => 'Kiosque',
			'post_types' => array( 'vf_publication' ),
			'content'    => $page( '<!-- wp:paragraph {"className":"section__surtitre"} --><p class="section__surtitre">Publications</p><!-- /wp:paragraph --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Kiosque</h1><!-- /wp:heading -->', '<!-- wp:vf/kiosque {"nombre":48,"format":"grille","niveau":2} /-->' ),
		)
	);
	register_block_template(
		'kiosque-villefontaine//single-vf_publication',
		array(
			'title'      => 'Publication du kiosque',
			'post_types' => array( 'vf_publication' ),
			'content'    => $page( '<!-- wp:post-title {"level":1} /--><!-- wp:post-excerpt /-->', '<!-- wp:vf/kiosque {"format":"seule"} /-->' ),
		)
	);
}
add_action( 'init', 'vf_kiosque_gabarits', 30 );
