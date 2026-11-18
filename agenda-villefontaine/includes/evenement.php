<?php
/**
 * Type de contenu « Événement », thématiques et champs (dates, horaires, lieu, répétition).
 *
 * @package AgendaVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Déclare le type « Événement » et la taxonomie « Thématique ».
 */
function vf_agenda_types() {
	register_post_type(
		'vf_evenement',
		array(
			'labels'        => array(
				'name'               => 'Agenda',
				'singular_name'      => 'Événement',
				'menu_name'          => 'Agenda',
				'add_new'            => 'Ajouter un événement',
				'add_new_item'       => 'Ajouter un événement',
				'edit_item'          => 'Modifier l’événement',
				'new_item'           => 'Nouvel événement',
				'view_item'          => 'Voir l’événement',
				'search_items'       => 'Rechercher un événement',
				'not_found'          => 'Aucun événement',
				'not_found_in_trash' => 'Aucun événement dans la corbeille',
				'all_items'          => 'Tous les événements',
			),
			'public'        => true,
			'has_archive'   => 'agenda',
			'rewrite'       => array( 'slug' => 'agenda', 'with_front' => false ),
			'menu_icon'     => 'dashicons-calendar-alt',
			'menu_position' => 6,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
			'show_in_rest'  => true,
			'template'      => array(
				array( 'core/paragraph', array( 'placeholder' => 'Décrivez l’événement : programme, public, conditions…' ) ),
			),
		)
	);
	register_taxonomy(
		'vf_theme_evenement',
		'vf_evenement',
		array(
			'labels'            => array(
				'name'          => 'Thématiques',
				'singular_name' => 'Thématique',
				'add_new_item'  => 'Ajouter une thématique',
				'search_items'  => 'Rechercher une thématique',
				'all_items'     => 'Toutes les thématiques',
				'edit_item'     => 'Modifier la thématique',
			),
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'agenda/thematique', 'with_front' => false ),
		)
	);
}
add_action( 'init', 'vf_agenda_types' );

/**
 * Champs d'un événement : clé => [type, libellé].
 *
 * @return array
 */
function vf_agenda_champs() {
	return array(
		'vf_debut'           => 'date',
		'vf_fin'             => 'date',
		'vf_heure_debut'     => 'heure',
		'vf_heure_fin'       => 'heure',
		'vf_lieu'            => 'texte',
		'vf_adresse'         => 'texte',
		'vf_recurrence'      => 'recurrence',
		'vf_recurrence_fin'  => 'date',
		'vf_infos'           => 'texte',
		'vf_lien'            => 'url',
		'vf_fin_effective'   => 'date', // Calculé : dernier jour où l'événement a lieu (sert au tri et au filtrage).
	);
}

/**
 * Nettoie une valeur selon son type.
 *
 * @param string $type   Type de champ.
 * @param mixed  $valeur Valeur brute.
 * @return string
 */
function vf_agenda_nettoie( $type, $valeur ) {
	$valeur = is_string( $valeur ) ? trim( wp_unslash( $valeur ) ) : '';
	switch ( $type ) {
		case 'date':
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $valeur ) && wp_checkdate( (int) substr( $valeur, 5, 2 ), (int) substr( $valeur, 8, 2 ), (int) substr( $valeur, 0, 4 ), $valeur ) ? $valeur : '';
		case 'heure':
			return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $valeur ) ? $valeur : '';
		case 'url':
			return esc_url_raw( $valeur );
		case 'recurrence':
			return array_key_exists( $valeur, vf_agenda_recurrences() ) ? $valeur : '';
		default:
			return sanitize_text_field( $valeur );
	}
}

/**
 * Répétitions possibles.
 *
 * @return array
 */
function vf_agenda_recurrences() {
	return array(
		''             => 'Ne se répète pas',
		'hebdo'        => 'Chaque semaine, le même jour',
		'quinzaine'    => 'Toutes les deux semaines',
		'mensuel_jour' => 'Chaque mois, le même jour de la semaine (ex. 1er samedi)',
		'mensuel_date' => 'Chaque mois, à la même date',
	);
}

/**
 * Déclare les champs pour l'API (lecture dans l'éditeur, import, export).
 */
function vf_agenda_meta() {
	foreach ( vf_agenda_champs() as $cle => $type ) {
		register_post_meta(
			'vf_evenement',
			$cle,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $v ) use ( $type ) {
					return vf_agenda_nettoie( $type, $v );
				},
				'auth_callback'     => function ( $autorise, $c, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
add_action( 'init', 'vf_agenda_meta' );

/**
 * Encadré « Date, horaires et lieu » sous l'éditeur.
 */
function vf_agenda_boite() {
	add_meta_box( 'vf-agenda-quand', 'Date, horaires et lieu', 'vf_agenda_boite_html', 'vf_evenement', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'vf_agenda_boite' );

/**
 * Formulaire de l'encadré.
 *
 * @param WP_Post $post Événement.
 */
function vf_agenda_boite_html( $post ) {
	wp_nonce_field( 'vf_agenda_enregistre', 'vf_agenda_jeton' );
	$v = array();
	foreach ( array_keys( vf_agenda_champs() ) as $cle ) {
		$v[ $cle ] = (string) get_post_meta( $post->ID, $cle, true );
	}
	?>
	<style>
		.vf-agenda-grille { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px 20px; }
		.vf-agenda-grille label { display: block; font-weight: 600; margin-bottom: 4px; }
		.vf-agenda-grille input, .vf-agenda-grille select { width: 100%; }
		.vf-agenda-aide { color: #50575e; margin: 4px 0 0; font-size: 12px; }
		.vf-agenda-boite h3 { margin: 18px 0 8px; font-size: 14px; }
	</style>
	<div class="vf-agenda-boite">
		<h3>Quand ?</h3>
		<div class="vf-agenda-grille">
			<p><label for="vf_debut">Date de début (obligatoire)</label><input type="date" id="vf_debut" name="vf_debut" value="<?php echo esc_attr( $v['vf_debut'] ); ?>" required></p>
			<p><label for="vf_fin">Date de fin</label><input type="date" id="vf_fin" name="vf_fin" value="<?php echo esc_attr( $v['vf_fin'] ); ?>"><span class="vf-agenda-aide">Seulement pour un événement sur plusieurs jours (exposition, festival…).</span></p>
			<p><label for="vf_heure_debut">Heure de début</label><input type="time" id="vf_heure_debut" name="vf_heure_debut" value="<?php echo esc_attr( $v['vf_heure_debut'] ); ?>"></p>
			<p><label for="vf_heure_fin">Heure de fin</label><input type="time" id="vf_heure_fin" name="vf_heure_fin" value="<?php echo esc_attr( $v['vf_heure_fin'] ); ?>"></p>
		</div>
		<h3>Se répète ?</h3>
		<div class="vf-agenda-grille">
			<p><label for="vf_recurrence">Répétition</label><select id="vf_recurrence" name="vf_recurrence">
				<?php foreach ( vf_agenda_recurrences() as $cle => $libelle ) : ?>
				<option value="<?php echo esc_attr( $cle ); ?>" <?php selected( $v['vf_recurrence'], $cle ); ?>><?php echo esc_html( $libelle ); ?></option>
				<?php endforeach; ?>
			</select></p>
			<p><label for="vf_recurrence_fin">Jusqu’au</label><input type="date" id="vf_recurrence_fin" name="vf_recurrence_fin" value="<?php echo esc_attr( $v['vf_recurrence_fin'] ); ?>"><span class="vf-agenda-aide">Laissé vide : la répétition s’arrête au bout d’un an.</span></p>
		</div>
		<h3>Où ?</h3>
		<div class="vf-agenda-grille">
			<p><label for="vf_lieu">Lieu</label><input type="text" id="vf_lieu" name="vf_lieu" value="<?php echo esc_attr( $v['vf_lieu'] ); ?>" placeholder="Ex. Médiathèque, carré Léon Blum"></p>
			<p><label for="vf_adresse">Adresse (facultatif)</label><input type="text" id="vf_adresse" name="vf_adresse" value="<?php echo esc_attr( $v['vf_adresse'] ); ?>"></p>
		</div>
		<h3>Infos pratiques</h3>
		<div class="vf-agenda-grille">
			<p><label for="vf_infos">Tarif, public, inscription</label><input type="text" id="vf_infos" name="vf_infos" value="<?php echo esc_attr( $v['vf_infos'] ); ?>" placeholder="Ex. Gratuit, dès 6 ans, sur inscription"></p>
			<p><label for="vf_lien">Lien d’inscription ou de billetterie</label><input type="url" id="vf_lien" name="vf_lien" value="<?php echo esc_attr( $v['vf_lien'] ); ?>" placeholder="https://"></p>
		</div>
		<p class="vf-agenda-aide">Pensez à choisir une <strong>thématique</strong> et une <strong>image mise en avant</strong> dans la colonne de droite : elles s’affichent sur l’accueil.</p>
	</div>
	<?php
}

/**
 * Enregistre l'encadré (jeton de sécurité + droits vérifiés).
 *
 * @param int $post_id Événement.
 */
function vf_agenda_enregistre( $post_id ) {
	if ( ! isset( $_POST['vf_agenda_jeton'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vf_agenda_jeton'] ) ), 'vf_agenda_enregistre' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( vf_agenda_champs() as $cle => $type ) {
		if ( 'vf_fin_effective' === $cle || ! isset( $_POST[ $cle ] ) ) {
			continue;
		}
		update_post_meta( $post_id, $cle, vf_agenda_nettoie( $type, $_POST[ $cle ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- nettoyé par vf_agenda_nettoie.
	}
	vf_agenda_calcule_fin( $post_id );
}
add_action( 'save_post_vf_evenement', 'vf_agenda_enregistre' );

/**
 * Calcule le dernier jour où l'événement a lieu (répétitions comprises).
 *
 * @param int $post_id Événement.
 */
function vf_agenda_calcule_fin( $post_id ) {
	$debut = (string) get_post_meta( $post_id, 'vf_debut', true );
	if ( '' === $debut ) {
		delete_post_meta( $post_id, 'vf_fin_effective' );
		return;
	}
	$fin = (string) get_post_meta( $post_id, 'vf_fin', true );
	$fin = ( $fin && $fin >= $debut ) ? $fin : $debut;
	if ( get_post_meta( $post_id, 'vf_recurrence', true ) ) {
		$rfin = (string) get_post_meta( $post_id, 'vf_recurrence_fin', true );
		$fin  = $rfin ? max( $fin, $rfin ) : gmdate( 'Y-m-d', strtotime( $debut . ' +1 year' ) );
	}
	update_post_meta( $post_id, 'vf_fin_effective', $fin );
}

/**
 * Les modifications faites par l'API (import, éditeur) recalculent aussi la fin effective.
 *
 * @param WP_Post $post Événement.
 */
function vf_agenda_apres_api( $post ) {
	vf_agenda_calcule_fin( $post->ID );
}
add_action( 'rest_after_insert_vf_evenement', 'vf_agenda_apres_api' );

/**
 * Colonne « Date » dans la liste des événements.
 *
 * @param array $colonnes Colonnes.
 * @return array
 */
function vf_agenda_colonnes( $colonnes ) {
	$nouv = array();
	foreach ( $colonnes as $cle => $libelle ) {
		$nouv[ $cle ] = $libelle;
		if ( 'title' === $cle ) {
			$nouv['vf_quand'] = 'Quand';
		}
	}
	return $nouv;
}
add_filter( 'manage_vf_evenement_posts_columns', 'vf_agenda_colonnes' );

/**
 * Contenu de la colonne « Quand ».
 *
 * @param string $colonne Colonne.
 * @param int    $post_id Événement.
 */
function vf_agenda_colonne_quand( $colonne, $post_id ) {
	if ( 'vf_quand' !== $colonne ) {
		return;
	}
	$debut = get_post_meta( $post_id, 'vf_debut', true );
	echo $debut ? esc_html( wp_date( 'j M Y', strtotime( $debut . ' 12:00' ) ) ) : '<em>Date manquante</em>';
	$rec = get_post_meta( $post_id, 'vf_recurrence', true );
	if ( $rec ) {
		echo '<br><small>' . esc_html( vf_agenda_recurrences()[ $rec ] ?? '' ) . '</small>';
	}
}
add_action( 'manage_vf_evenement_posts_custom_column', 'vf_agenda_colonne_quand', 10, 2 );
