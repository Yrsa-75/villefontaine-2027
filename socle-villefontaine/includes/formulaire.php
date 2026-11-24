<?php
/**
 * Formulaire de contact : jeton anti-robots, réception des messages, envoi au service,
 * conservation dans l'admin (menu « Messages ») et suppression automatique après 12 mois.
 *
 * Aucun service tiers : pas de reCAPTCHA. Protection par champ piège, jeton signé
 * (âge minimal et maximal) et limite d'envois par adresse IP (stockée sous forme d'empreinte).
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Services valides d'un bloc (libellé et adresse non vides).
 *
 * @param array $destinataires Liste saisie dans le bloc.
 * @return array
 */
function vf_socle_contact_services( $destinataires ) {
	$services = array();
	foreach ( (array) $destinataires as $d ) {
		$libelle  = isset( $d['libelle'] ) ? sanitize_text_field( $d['libelle'] ) : '';
		$courriel = isset( $d['courriel'] ) ? sanitize_email( $d['courriel'] ) : '';
		if ( $libelle && is_email( $courriel ) ) {
			$services[] = array(
				'libelle'  => $libelle,
				'courriel' => $courriel,
			);
		}
	}
	if ( ! $services ) {
		$services[] = array(
			'libelle'  => 'Mairie',
			'courriel' => get_option( 'admin_email' ),
		);
	}
	return $services;
}

/**
 * Type de contenu interne « Message » (non public).
 */
function vf_socle_type_message() {
	register_post_type(
		'vf_message',
		array(
			'labels'          => array(
				'name'          => 'Messages',
				'singular_name' => 'Message',
				'menu_name'     => 'Messages reçus',
				'all_items'     => 'Tous les messages',
				'edit_item'     => 'Message',
				'search_items'  => 'Rechercher un message',
				'not_found'     => 'Aucun message reçu pour l’instant.',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 26,
			'supports'        => array( 'title' ),
			'capability_type' => 'page',
			'map_meta_cap'    => true,
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		)
	);
}
add_action( 'init', 'vf_socle_type_message' );

/**
 * Routes REST : jeton (GET) et envoi (POST). Publiques par nature, protégées ci-dessous.
 */
function vf_socle_routes_contact() {
	register_rest_route(
		'vf-socle/v1',
		'/jeton',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => static function () {
				$t   = time();
				$rep = rest_ensure_response( array( 'jeton' => $t . '.' . hash_hmac( 'sha256', (string) $t, wp_salt( 'nonce' ) ) ) );
				$rep->header( 'Cache-Control', 'no-store, private' );
				do_action( 'litespeed_control_set_nocache', 'jeton du formulaire de contact' );
				return $rep;
			},
		)
	);
	register_rest_route(
		'vf-socle/v1',
		'/contact',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'vf_socle_recevoir_contact',
		)
	);
}
add_action( 'rest_api_init', 'vf_socle_routes_contact' );

/**
 * Réception d'un message.
 *
 * @param WP_REST_Request $r Requête.
 * @return WP_REST_Response|WP_Error
 */
function vf_socle_recevoir_contact( WP_REST_Request $r ) {
	$refus = new WP_Error( 'refus', 'L’envoi n’a pas abouti. Rechargez la page puis réessayez, ou appelez le 04 74 96 00 00.', array( 'status' => 400 ) );

	// 1. Champ piège : un humain ne le voit pas, un robot le remplit. On fait semblant d'accepter.
	if ( '' !== trim( (string) $r->get_param( 'site_web' ) ) ) {
		return rest_ensure_response( array( 'ok' => true ) );
	}

	// 2. Jeton signé : au moins 3 secondes et au plus 6 heures entre l'affichage et l'envoi.
	$jeton = explode( '.', (string) $r->get_param( 'jeton' ) );
	if ( 2 !== count( $jeton ) || ! hash_equals( hash_hmac( 'sha256', $jeton[0], wp_salt( 'nonce' ) ), $jeton[1] ) ) {
		return $refus;
	}
	$age = time() - (int) $jeton[0];
	if ( $age < 3 || $age > 6 * HOUR_IN_SECONDS ) {
		return $refus;
	}

	// 3. Limite : 5 messages par tranche de 15 minutes et par adresse IP (empreinte, jamais l'IP en clair).
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$cle   = 'vf_contact_' . substr( hash_hmac( 'sha256', $ip, wp_salt() ), 0, 20 );
	$envois = (int) get_transient( $cle );
	if ( $envois >= 5 ) {
		return new WP_Error( 'limite', 'Vous avez envoyé plusieurs messages en peu de temps. Merci de patienter un quart d’heure.', array( 'status' => 429 ) );
	}

	// 4. Destinataire et modèle lus dans la page elle-même : impossible d'envoyer à une adresse choisie par le visiteur.
	$page = get_post( (int) $r->get_param( 'page' ) );
	if ( ! $page || 'publish' !== $page->post_status ) {
		return $refus;
	}
	$bloc = null;
	foreach ( parse_blocks( $page->post_content ) as $b ) {
		if ( 'vf/formulaire-contact' === $b['blockName'] ) {
			$bloc = $b;
			break;
		}
	}
	if ( ! $bloc ) {
		return $refus;
	}
	$services = vf_socle_contact_services( $bloc['attrs']['destinataires'] ?? array() );
	$index    = (int) $r->get_param( 'service' );
	if ( ! isset( $services[ $index ] ) ) {
		return $refus;
	}
	$service     = $services[ $index ];
	$signalement = 'signalement' === ( $bloc['attrs']['modele'] ?? 'contact' );

	// 5. Données.
	$nom      = sanitize_text_field( (string) $r->get_param( 'nom' ) );
	$courriel = sanitize_email( (string) $r->get_param( 'courriel' ) );
	$tel      = sanitize_text_field( (string) $r->get_param( 'telephone' ) );
	$lieu     = $signalement ? sanitize_text_field( (string) $r->get_param( 'lieu' ) ) : '';
	$message  = sanitize_textarea_field( (string) $r->get_param( 'message' ) );
	$accord   = (bool) $r->get_param( 'accord' );
	// Signalement : pas de champ « objet », il est rédigé à partir de la nature et du lieu.
	$objet = $signalement
		? ( $lieu ? 'Signalement : ' . $service['libelle'] . ' (' . mb_substr( $lieu, 0, 120 ) . ')' : '' )
		: sanitize_text_field( (string) $r->get_param( 'objet' ) );
	if ( ! $nom || ! is_email( $courriel ) || ! $objet || ! $message || ! $accord ) {
		return new WP_Error( 'champs', 'Certains champs obligatoires sont vides ou incorrects.', array( 'status' => 400 ) );
	}
	if ( mb_strlen( $message ) > 5000 || mb_strlen( $nom . $objet . $lieu ) > 600 ) {
		return new WP_Error( 'champs', 'Votre message est trop long (5 000 caractères au plus).', array( 'status' => 400 ) );
	}

	// 6. Conservation dans l'admin.
	$id = wp_insert_post(
		array(
			'post_type'    => 'vf_message',
			'post_status'  => 'private',
			'post_title'   => wp_slash( $objet ),
			'post_content' => wp_slash( $message ),
		),
		true
	);
	if ( ! is_wp_error( $id ) ) {
		update_post_meta( $id, 'vf_nom', $nom );
		update_post_meta( $id, 'vf_courriel', $courriel );
		update_post_meta( $id, 'vf_tel', $tel );
		update_post_meta( $id, 'vf_service', $service['libelle'] );
		update_post_meta( $id, 'vf_page', $page->ID );
		if ( $signalement ) {
			update_post_meta( $id, 'vf_lieu', $lieu );
		}
	}

	// 7. Envoi au service, avec « Répondre » directement au visiteur.
	$corps  = "Nouveau message reçu depuis le site (page « " . get_the_title( $page ) . " »).\n\n";
	if ( $signalement ) {
		$corps .= "Nature du signalement : {$service['libelle']}\nLieu concerné : {$lieu}\nNom : {$nom}\nCourriel : {$courriel}\n" . ( $tel ? "Téléphone : {$tel}\n" : '' ) . "\n{$message}\n\n";
	} else {
		$corps .= "Service : {$service['libelle']}\nNom : {$nom}\nCourriel : {$courriel}\n" . ( $tel ? "Téléphone : {$tel}\n" : '' ) . "Objet : {$objet}\n\n{$message}\n\n";
	}
	$corps .= "Pour répondre, utilisez simplement « Répondre » dans votre messagerie.\n";
	if ( ! is_wp_error( $id ) ) {
		$corps .= 'Message conservé dans l’admin : ' . admin_url( 'post.php?post=' . $id . '&action=edit' ) . "\n";
	}
	$nom_entete = str_replace( array( "\r", "\n", '"', '<', '>' ), '', $nom );
	$envoye     = wp_mail(
		$service['courriel'],
		'[Site de la ville] ' . $objet,
		$corps,
		array( 'Reply-To: "' . $nom_entete . '" <' . $courriel . '>' )
	);
	if ( ! is_wp_error( $id ) ) {
		update_post_meta( $id, 'vf_envoye', $envoye ? 1 : 0 );
	}
	set_transient( $cle, $envois + 1, 15 * MINUTE_IN_SECONDS );

	if ( ! $envoye && is_wp_error( $id ) ) {
		return new WP_Error( 'envoi', 'L’envoi n’a pas abouti. Réessayez plus tard ou appelez le 04 74 96 00 00.', array( 'status' => 500 ) );
	}
	return rest_ensure_response( array( 'ok' => true ) );
}

/**
 * Admin : colonnes de la liste des messages.
 */
add_filter(
	'manage_vf_message_posts_columns',
	static function () {
		return array(
			'cb'         => '<input type="checkbox">',
			'title'      => 'Objet',
			'vf_nom'     => 'Expéditeur',
			'vf_service' => 'Service',
			'vf_envoye'  => 'Courriel',
			'date'       => 'Reçu le',
		);
	}
);
add_action(
	'manage_vf_message_posts_custom_column',
	static function ( $colonne, $id ) {
		if ( 'vf_nom' === $colonne ) {
			echo esc_html( get_post_meta( $id, 'vf_nom', true ) ) . '<br><a href="mailto:' . esc_attr( get_post_meta( $id, 'vf_courriel', true ) ) . '">' . esc_html( get_post_meta( $id, 'vf_courriel', true ) ) . '</a>';
		} elseif ( 'vf_service' === $colonne ) {
			echo esc_html( get_post_meta( $id, 'vf_service', true ) );
		} elseif ( 'vf_envoye' === $colonne ) {
			echo get_post_meta( $id, 'vf_envoye', true ) ? 'Transmis' : '<strong>Non transmis</strong>';
		}
	},
	10,
	2
);

/**
 * Admin : lecture d'un message (sous le titre, en lecture seule).
 */
add_action(
	'edit_form_after_title',
	static function ( $post ) {
		if ( 'vf_message' !== $post->post_type ) {
			return;
		}
		$m = static function ( $k ) use ( $post ) {
			return (string) get_post_meta( $post->ID, $k, true );
		};
		echo '<div class="postbox" style="margin-top:16px;padding:16px 20px">';
		if ( '' !== $m( 'vf_lieu' ) ) {
			echo '<p><strong>Nature du signalement :</strong> ' . esc_html( $m( 'vf_service' ) ) . '</p>';
			echo '<p><strong>Lieu concerné :</strong> ' . esc_html( $m( 'vf_lieu' ) ) . '</p>';
		} else {
			echo '<p><strong>Service :</strong> ' . esc_html( $m( 'vf_service' ) ) . '</p>';
		}
		echo '<p><strong>Expéditeur :</strong> ' . esc_html( $m( 'vf_nom' ) ) . ' (<a href="mailto:' . esc_attr( $m( 'vf_courriel' ) ) . '?subject=' . rawurlencode( 'Re : ' . $post->post_title ) . '">' . esc_html( $m( 'vf_courriel' ) ) . '</a>)' . ( $m( 'vf_tel' ) ? ', ' . esc_html( $m( 'vf_tel' ) ) : '' ) . '</p>';
		if ( $m( 'vf_page' ) ) {
			echo '<p><strong>Envoyé depuis :</strong> <a href="' . esc_url( get_permalink( (int) $m( 'vf_page' ) ) ) . '">' . esc_html( get_the_title( (int) $m( 'vf_page' ) ) ) . '</a></p>';
		}
		echo '<p><strong>Courriel au service :</strong> ' . ( $m( 'vf_envoye' ) ? 'transmis' : 'non transmis (vérifier la configuration d’envoi des courriels)' ) . '</p>';
		echo '<hr><div style="white-space:pre-wrap;font-size:15px;line-height:1.6">' . esc_html( $post->post_content ) . '</div>';
		echo '<p class="description" style="margin-top:16px">Ce message sera supprimé automatiquement 12 mois après sa réception (RGPD).</p></div>';
	}
);

/**
 * RGPD : suppression quotidienne des messages de plus de 12 mois.
 */
function vf_socle_purge_messages() {
	$vieux = get_posts(
		array(
			'post_type'      => 'vf_message',
			'post_status'    => 'any',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'date_query'     => array( array( 'before' => '12 months ago' ) ),
		)
	);
	foreach ( $vieux as $id ) {
		wp_delete_post( $id, true );
	}
}
add_action( 'vf_socle_purge_messages', 'vf_socle_purge_messages' );
add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'vf_socle_purge_messages' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'vf_socle_purge_messages' );
		}
	}
);
