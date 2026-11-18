<?php
/**
 * Type de contenu « Lieu », catégories (avec couleur) et coordonnées.
 *
 * @package AnnuaireVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Couleurs proposées pour les catégories (toutes lisibles sur fond clair, contraste vérifié).
 *
 * @return array
 */
function vf_annuaire_couleurs() {
	return array(
		'#246170' => 'Bleu Villefontaine',
		'#B54A1F' => 'Brique',
		'#5A7D1F' => 'Vert',
		'#9A6100' => 'Ocre',
		'#3D4E8A' => 'Bleu nuit',
		'#7A4E9C' => 'Violet',
		'#A3305F' => 'Framboise',
		'#2F6B4F' => 'Vert sapin',
	);
}

/**
 * Déclare le type « Lieu » et la taxonomie « Catégorie de lieu ».
 */
function vf_annuaire_types() {
	register_post_type(
		'vf_lieu',
		array(
			'labels'        => array(
				'name'               => 'Annuaire des lieux',
				'singular_name'      => 'Lieu',
				'menu_name'          => 'Annuaire',
				'add_new'            => 'Ajouter un lieu',
				'add_new_item'       => 'Ajouter un lieu',
				'edit_item'          => 'Modifier le lieu',
				'view_item'          => 'Voir la fiche',
				'search_items'       => 'Rechercher un lieu',
				'not_found'          => 'Aucun lieu',
				'not_found_in_trash' => 'Aucun lieu dans la corbeille',
				'all_items'          => 'Tous les lieux',
			),
			'public'        => true,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'lieu', 'with_front' => false ),
			'menu_icon'     => 'dashicons-location-alt',
			'menu_position' => 7,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
			'show_in_rest'  => true,
		)
	);
	register_taxonomy(
		'vf_categorie_lieu',
		'vf_lieu',
		array(
			'labels'            => array(
				'name'          => 'Catégories de lieux',
				'singular_name' => 'Catégorie',
				'menu_name'     => 'Catégories',
				'add_new_item'  => 'Ajouter une catégorie',
				'edit_item'     => 'Modifier la catégorie',
				'all_items'     => 'Toutes les catégories',
			),
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => false,
			'public'            => false,
			'show_ui'           => true,
		)
	);
	register_term_meta(
		'vf_categorie_lieu',
		'vf_couleur',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => function ( $v ) {
				return array_key_exists( $v, vf_annuaire_couleurs() ) ? $v : '#246170';
			},
		)
	);
}
add_action( 'init', 'vf_annuaire_types' );

/**
 * Champs d'un lieu : clé => libellé.
 *
 * @return array
 */
function vf_annuaire_champs() {
	return array(
		'vf_adresse'     => 'Adresse',
		'vf_code_postal' => 'Code postal',
		'vf_ville'       => 'Ville',
		'vf_lat'         => 'Latitude',
		'vf_lng'         => 'Longitude',
		'vf_tel'         => 'Téléphone',
		'vf_courriel'    => 'Courriel',
		'vf_site'        => 'Site internet',
		'vf_horaires'    => 'Horaires',
		'vf_pmr'         => 'Accessible PMR (oui / non)',
	);
}

/**
 * Nettoie la valeur d'un champ.
 *
 * @param string $cle    Champ.
 * @param mixed  $valeur Valeur brute.
 * @return string
 */
function vf_annuaire_nettoie( $cle, $valeur ) {
	$valeur = is_scalar( $valeur ) ? trim( (string) $valeur ) : '';
	switch ( $cle ) {
		case 'vf_lat':
		case 'vf_lng':
			$valeur = str_replace( ',', '.', $valeur );
			if ( ! is_numeric( $valeur ) ) {
				return '';
			}
			$n = (float) $valeur;
			$max = 'vf_lat' === $cle ? 90 : 180;
			return abs( $n ) <= $max ? (string) round( $n, 6 ) : '';
		case 'vf_courriel':
			return sanitize_email( $valeur );
		case 'vf_site':
			return esc_url_raw( $valeur );
		case 'vf_horaires':
			return sanitize_textarea_field( $valeur );
		case 'vf_pmr':
			return in_array( strtolower( $valeur ), array( 'oui', '1', 'true', 'yes' ), true ) ? 'oui' : ( '' === $valeur ? '' : 'non' );
		default:
			return sanitize_text_field( $valeur );
	}
}

/**
 * Déclare les champs pour l'API.
 */
function vf_annuaire_meta() {
	foreach ( array_keys( vf_annuaire_champs() ) as $cle ) {
		register_post_meta(
			'vf_lieu',
			$cle,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $v ) use ( $cle ) {
					return vf_annuaire_nettoie( $cle, $v );
				},
				'auth_callback'     => function ( $autorise, $c, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
add_action( 'init', 'vf_annuaire_meta' );

/**
 * Encadré « Coordonnées » sous l'éditeur.
 */
function vf_annuaire_boite() {
	add_meta_box( 'vf-annuaire-coordonnees', 'Coordonnées et position sur la carte', 'vf_annuaire_boite_html', 'vf_lieu', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'vf_annuaire_boite' );

/**
 * Formulaire de l'encadré.
 *
 * @param WP_Post $post Lieu.
 */
function vf_annuaire_boite_html( $post ) {
	wp_nonce_field( 'vf_annuaire_enregistre', 'vf_annuaire_jeton' );
	$v = array();
	foreach ( array_keys( vf_annuaire_champs() ) as $cle ) {
		$v[ $cle ] = (string) get_post_meta( $post->ID, $cle, true );
	}
	if ( '' === $v['vf_code_postal'] && '' === $v['vf_adresse'] ) {
		$v['vf_code_postal'] = '38090';
		$v['vf_ville']       = 'Villefontaine';
	}
	?>
	<style>
		.vf-lieu-grille { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px 20px; }
		.vf-lieu-grille label { display: block; font-weight: 600; margin-bottom: 4px; }
		.vf-lieu-grille input, .vf-lieu-grille textarea, .vf-lieu-grille select { width: 100%; }
		.vf-lieu-aide { color: #50575e; font-size: 12px; margin: 4px 0 0; }
	</style>
	<div class="vf-lieu-grille">
		<p><label for="vf_adresse">Adresse</label><input type="text" id="vf_adresse" name="vf_adresse" value="<?php echo esc_attr( $v['vf_adresse'] ); ?>" placeholder="Ex. Place Pierre Mendès France"></p>
		<p><label for="vf_code_postal">Code postal</label><input type="text" id="vf_code_postal" name="vf_code_postal" value="<?php echo esc_attr( $v['vf_code_postal'] ); ?>"></p>
		<p><label for="vf_ville">Ville</label><input type="text" id="vf_ville" name="vf_ville" value="<?php echo esc_attr( $v['vf_ville'] ); ?>"></p>
	</div>
	<p><button type="button" class="button" id="vf-localiser">Placer automatiquement sur la carte à partir de l’adresse</button> <span id="vf-localiser-resultat" class="vf-lieu-aide" aria-live="polite"></span></p>
	<div class="vf-lieu-grille">
		<p><label for="vf_lat">Latitude</label><input type="text" id="vf_lat" name="vf_lat" value="<?php echo esc_attr( $v['vf_lat'] ); ?>" inputmode="decimal" placeholder="45.6135"></p>
		<p><label for="vf_lng">Longitude</label><input type="text" id="vf_lng" name="vf_lng" value="<?php echo esc_attr( $v['vf_lng'] ); ?>" inputmode="decimal" placeholder="5.1500"><span class="vf-lieu-aide">Sans coordonnées, le lieu n’apparaît pas sur la carte.</span></p>
		<p><label for="vf_tel">Téléphone</label><input type="tel" id="vf_tel" name="vf_tel" value="<?php echo esc_attr( $v['vf_tel'] ); ?>"></p>
		<p><label for="vf_courriel">Courriel</label><input type="email" id="vf_courriel" name="vf_courriel" value="<?php echo esc_attr( $v['vf_courriel'] ); ?>"></p>
		<p><label for="vf_site">Site internet</label><input type="url" id="vf_site" name="vf_site" value="<?php echo esc_attr( $v['vf_site'] ); ?>" placeholder="https://"></p>
		<p><label for="vf_pmr">Accessible aux personnes à mobilité réduite</label><select id="vf_pmr" name="vf_pmr"><option value="">Non renseigné</option><option value="oui" <?php selected( $v['vf_pmr'], 'oui' ); ?>>Oui</option><option value="non" <?php selected( $v['vf_pmr'], 'non' ); ?>>Non</option></select></p>
	</div>
	<p><label for="vf_horaires" style="font-weight:600;display:block;margin-bottom:4px">Horaires</label><textarea id="vf_horaires" name="vf_horaires" rows="3" style="width:100%"><?php echo esc_textarea( $v['vf_horaires'] ); ?></textarea></p>
	<script>
	/* Recherche des coordonnées dans la Base Adresse Nationale (service public, sans clé). Uniquement dans l'administration. */
	document.getElementById('vf-localiser').addEventListener('click', function () {
		var q = [document.getElementById('vf_adresse').value, document.getElementById('vf_code_postal').value, document.getElementById('vf_ville').value].join(' ').trim();
		var sortie = document.getElementById('vf-localiser-resultat');
		if (!q) { sortie.textContent = 'Saisissez d’abord une adresse.'; return; }
		sortie.textContent = 'Recherche…';
		fetch('https://api-adresse.data.gouv.fr/search/?limit=1&q=' + encodeURIComponent(q))
			.then(function (r) { return r.json(); })
			.then(function (d) {
				if (!d.features || !d.features.length) { sortie.textContent = 'Adresse introuvable : vérifiez-la ou saisissez les coordonnées.'; return; }
				var c = d.features[0].geometry.coordinates;
				document.getElementById('vf_lat').value = c[1].toFixed(6);
				document.getElementById('vf_lng').value = c[0].toFixed(6);
				sortie.textContent = 'Trouvé : ' + d.features[0].properties.label + '. Pensez à enregistrer.';
			})
			.catch(function () { sortie.textContent = 'Le service d’adresses ne répond pas. Réessayez plus tard.'; });
	});
	</script>
	<?php
}

/**
 * Enregistre l'encadré.
 *
 * @param int $post_id Lieu.
 */
function vf_annuaire_enregistre( $post_id ) {
	if ( ! isset( $_POST['vf_annuaire_jeton'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vf_annuaire_jeton'] ) ), 'vf_annuaire_enregistre' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array_keys( vf_annuaire_champs() ) as $cle ) {
		if ( isset( $_POST[ $cle ] ) ) {
			update_post_meta( $post_id, $cle, vf_annuaire_nettoie( $cle, wp_unslash( $_POST[ $cle ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- nettoyé par vf_annuaire_nettoie.
		}
	}
}
add_action( 'save_post_vf_lieu', 'vf_annuaire_enregistre' );

/* ---------------------------------------------------------------------------
   Couleur des catégories (écran Annuaire > Catégories)
   --------------------------------------------------------------------------- */

/**
 * Liste déroulante de couleur.
 *
 * @param string $actuelle Couleur actuelle.
 */
function vf_annuaire_choix_couleur( $actuelle ) {
	wp_nonce_field( 'vf_annuaire_couleur', 'vf_annuaire_couleur_jeton' );
	echo '<select name="vf_couleur" id="vf_couleur">';
	foreach ( vf_annuaire_couleurs() as $hex => $nom ) {
		printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $hex ), selected( $actuelle, $hex, false ), esc_html( $nom ) );
	}
	echo '</select><p class="description">Couleur de l’épingle sur la carte.</p>';
}
add_action(
	'vf_categorie_lieu_add_form_fields',
	function () {
		echo '<div class="form-field"><label for="vf_couleur">Couleur</label>';
		vf_annuaire_choix_couleur( '#246170' );
		echo '</div>';
	}
);
add_action(
	'vf_categorie_lieu_edit_form_fields',
	function ( $terme ) {
		echo '<tr class="form-field"><th scope="row"><label for="vf_couleur">Couleur</label></th><td>';
		vf_annuaire_choix_couleur( (string) get_term_meta( $terme->term_id, 'vf_couleur', true ) );
		echo '</td></tr>';
	}
);

/**
 * Enregistre la couleur d'une catégorie.
 *
 * @param int $term_id Catégorie.
 */
function vf_annuaire_enregistre_couleur( $term_id ) {
	if ( ! isset( $_POST['vf_annuaire_couleur_jeton'], $_POST['vf_couleur'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vf_annuaire_couleur_jeton'] ) ), 'vf_annuaire_couleur' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$couleur = sanitize_text_field( wp_unslash( $_POST['vf_couleur'] ) );
	update_term_meta( $term_id, 'vf_couleur', array_key_exists( $couleur, vf_annuaire_couleurs() ) ? $couleur : '#246170' );
}
add_action( 'created_vf_categorie_lieu', 'vf_annuaire_enregistre_couleur' );
add_action( 'edited_vf_categorie_lieu', 'vf_annuaire_enregistre_couleur' );

/**
 * Lieux à afficher sur la carte (avec coordonnées).
 *
 * @param int[] $categories Catégories à inclure (vide = toutes).
 * @return array{points:array,categories:array}
 */
function vf_annuaire_donnees( $categories = array() ) {
	$args = array(
		'post_type'      => 'vf_lieu',
		'post_status'    => 'publish',
		'posts_per_page' => 500,
		'no_found_rows'  => true,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);
	if ( $categories ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'vf_categorie_lieu', 'terms' => array_map( 'intval', $categories ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$points = array();
	$cats   = array();
	foreach ( get_posts( $args ) as $lieu ) {
		$lat = get_post_meta( $lieu->ID, 'vf_lat', true );
		$lng = get_post_meta( $lieu->ID, 'vf_lng', true );
		if ( '' === $lat || '' === $lng ) {
			continue;
		}
		$termes = get_the_terms( $lieu, 'vf_categorie_lieu' );
		$terme  = ( $termes && ! is_wp_error( $termes ) ) ? $termes[0] : null;
		$nom_c  = $terme ? $terme->name : 'Autres lieux';
		if ( ! isset( $cats[ $nom_c ] ) ) {
			$c              = $terme ? (string) get_term_meta( $terme->term_id, 'vf_couleur', true ) : '';
			$cats[ $nom_c ] = $c ? $c : '#246170';
		}
		$adresse  = trim( get_post_meta( $lieu->ID, 'vf_adresse', true ) );
		$points[] = array(
			'id'        => 'lieu-' . $lieu->ID,
			'nom'       => get_the_title( $lieu ),
			'categorie' => $nom_c,
			'adresse'   => $adresse,
			'ville'     => trim( get_post_meta( $lieu->ID, 'vf_code_postal', true ) . ' ' . get_post_meta( $lieu->ID, 'vf_ville', true ) ),
			'lat'       => (float) $lat,
			'lng'       => (float) $lng,
			'telephone' => (string) get_post_meta( $lieu->ID, 'vf_tel', true ),
			'horaires'  => (string) get_post_meta( $lieu->ID, 'vf_horaires', true ),
			'url'       => get_permalink( $lieu ),
		);
	}
	ksort( $cats );
	return array( 'points' => $points, 'categories' => $cats );
}
