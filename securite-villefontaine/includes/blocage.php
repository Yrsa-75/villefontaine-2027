<?php
/**
 * Protection contre le forçage des mots de passe.
 *
 * Après N échecs de connexion (3 par défaut), l'adresse IP est bloquée pour la connexion pendant
 * une durée réglable (1 heure par défaut). Les personnes déjà connectées ne sont pas déconnectées :
 * un administrateur peut donc débloquer une adresse depuis Réglages > Sécurité de la connexion,
 * ou depuis le lien reçu par courriel.
 *
 * Toutes les portes d'entrée sont couvertes : formulaire de connexion, mots de passe d'application
 * (API REST). XML-RPC, autre cible classique des robots, est désactivé.
 *
 * Données conservées : adresse IP et identifiant essayé, le temps du blocage, et un journal
 * des 200 derniers événements sur 30 jours au plus.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Données ---------- */

/**
 * Blocages en cours (les blocages échus sont retirés au passage).
 *
 * @return array adresse IP => [ debut, fin, identifiant ].
 */
function vf_securite_blocages() {
	$tous    = (array) get_option( 'vf_securite_blocages', array() );
	$actifs  = array();
	$instant = time();
	foreach ( $tous as $ip => $b ) {
		if ( is_array( $b ) && ! empty( $b['fin'] ) && (int) $b['fin'] > $instant ) {
			$actifs[ $ip ] = $b;
		}
	}
	if ( count( $actifs ) !== count( $tous ) ) {
		update_option( 'vf_securite_blocages', $actifs, false );
	}
	return $actifs;
}

/**
 * Ajoute une ligne au journal.
 *
 * @param string $type        echec | blocage | deblocage | refus.
 * @param string $ip          Adresse IP.
 * @param string $identifiant Identifiant essayé, ou précision.
 */
function vf_securite_journal( $type, $ip, $identifiant = '' ) {
	$journal   = (array) get_option( 'vf_securite_journal', array() );
	$journal[] = array( time(), $type, $ip, mb_substr( sanitize_text_field( (string) $identifiant ), 0, 60 ) );
	$limite    = time() - 30 * DAY_IN_SECONDS;
	$journal   = array_values(
		array_filter(
			$journal,
			static function ( $l ) use ( $limite ) {
				return is_array( $l ) && (int) $l[0] >= $limite;
			}
		)
	);
	update_option( 'vf_securite_journal', array_slice( $journal, -200 ), false );
}

/**
 * Une adresse est-elle dans la liste de confiance ?
 *
 * @param string $ip Adresse IP.
 * @return bool
 */
function vf_securite_de_confiance( $ip ) {
	return in_array( $ip, vf_securite_reglages()['confiance'], true );
}

/**
 * Blocage en cours pour une adresse.
 *
 * @param string $ip Adresse IP.
 * @return array|null [ debut, fin, identifiant ] ou null.
 */
function vf_securite_blocage_de( $ip ) {
	if ( '' === $ip || vf_securite_en_pause() || vf_securite_de_confiance( $ip ) ) {
		return null;
	}
	$blocages = vf_securite_blocages();
	return $blocages[ $ip ] ?? null;
}

/**
 * Nombre d'échecs récents d'une adresse.
 *
 * @param string $ip Adresse IP.
 * @return int
 */
function vf_securite_echecs( $ip ) {
	return (int) get_transient( 'vf_sec_' . md5( $ip ) );
}

/**
 * Minutes restantes d'un blocage (arrondies au-dessus).
 *
 * @param array $blocage Blocage.
 * @return int
 */
function vf_securite_minutes_restantes( $blocage ) {
	return max( 1, (int) ceil( ( (int) $blocage['fin'] - time() ) / MINUTE_IN_SECONDS ) );
}

/**
 * Message affiché à une adresse bloquée.
 *
 * @param array $blocage Blocage.
 * @return string
 */
function vf_securite_message_blocage( $blocage ) {
	$min = vf_securite_minutes_restantes( $blocage );
	return sprintf(
		'<strong>Trop de tentatives de connexion.</strong> Par sécurité, la connexion est bloquée depuis votre adresse pendant encore %s. Un administrateur du site peut lever ce blocage.',
		1 === $min ? '1 minute' : $min . ' minutes'
	);
}

/**
 * Bloque une adresse.
 *
 * @param string $ip          Adresse IP.
 * @param string $identifiant Dernier identifiant essayé.
 */
function vf_securite_bloquer( $ip, $identifiant ) {
	$r        = vf_securite_reglages();
	$blocages = vf_securite_blocages();
	// Garde-fou : en cas d'attaque depuis des milliers d'adresses, la liste ne grossit pas sans fin.
	if ( count( $blocages ) >= 2000 ) {
		$blocages = array_slice( $blocages, -1500, null, true );
	}
	$blocages[ $ip ] = array(
		'debut'       => time(),
		'fin'         => time() + $r['duree'] * MINUTE_IN_SECONDS,
		'identifiant' => mb_substr( sanitize_text_field( (string) $identifiant ), 0, 60 ),
	);
	update_option( 'vf_securite_blocages', $blocages, false );
	delete_transient( 'vf_sec_' . md5( $ip ) );
	vf_securite_journal( 'blocage', $ip, $identifiant );
	vf_securite_prevenir( $ip, $blocages[ $ip ] );
}

/**
 * Lève le blocage d'une adresse.
 *
 * @param string $ip  Adresse IP.
 * @param string $par Qui débloque (pour le journal).
 * @return bool Vrai si un blocage a été levé.
 */
function vf_securite_debloquer( $ip, $par ) {
	$blocages = vf_securite_blocages();
	if ( ! isset( $blocages[ $ip ] ) ) {
		return false;
	}
	unset( $blocages[ $ip ] );
	update_option( 'vf_securite_blocages', $blocages, false );
	delete_transient( 'vf_sec_' . md5( $ip ) );
	vf_securite_journal( 'deblocage', $ip, $par );
	return true;
}

/**
 * Compte un échec ; bloque l'adresse quand la limite est atteinte.
 *
 * @param string $ip          Adresse IP.
 * @param string $identifiant Identifiant essayé.
 */
function vf_securite_compter_echec( $ip, $identifiant ) {
	if ( '' === $ip || vf_securite_en_pause() ) {
		return;
	}
	$r = vf_securite_reglages();
	$n = vf_securite_echecs( $ip ) + 1;
	vf_securite_journal( 'echec', $ip, $identifiant );
	if ( vf_securite_de_confiance( $ip ) ) {
		return;
	}
	if ( $n >= $r['tentatives'] ) {
		vf_securite_bloquer( $ip, $identifiant );
		return;
	}
	// Les échecs sont oubliés si l'on reste plus longtemps que la durée du blocage sans se tromper à nouveau.
	set_transient( 'vf_sec_' . md5( $ip ), $n, $r['duree'] * MINUTE_IN_SECONDS );
}

/* ---------- Formulaire de connexion ---------- */

/**
 * Avant toute vérification du mot de passe : une adresse bloquée est refusée d'emblée.
 *
 * @param mixed  $user     Utilisateur, erreur ou null.
 * @param string $username Identifiant saisi.
 * @param string $password Mot de passe saisi.
 * @return mixed
 */
function vf_securite_refuser_adresse_bloquee( $user, $username, $password ) {
	if ( '' === (string) $username && '' === (string) $password ) {
		return $user; // Simple affichage de la page.
	}
	$blocage = vf_securite_blocage_de( vf_securite_ip() );
	if ( ! $blocage ) {
		return $user;
	}
	// Le mot de passe n'est même pas vérifié : aucune information ne filtre, aucun calcul inutile.
	remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
	remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
	remove_filter( 'authenticate', 'wp_authenticate_application_password', 20 );
	return new WP_Error( 'vf_securite_bloquee', vf_securite_message_blocage( $blocage ) );
}
add_filter( 'authenticate', 'vf_securite_refuser_adresse_bloquee', 1, 3 );

/**
 * Après les vérifications de WordPress : le blocage s'impose quoi qu'il arrive, et les messages
 * d'erreur ne disent plus si c'est l'identifiant ou le mot de passe qui est faux.
 *
 * @param mixed  $user     Utilisateur ou erreur.
 * @param string $username Identifiant saisi.
 * @return mixed
 */
function vf_securite_message_d_echec( $user, $username ) {
	if ( vf_securite_en_pause() ) {
		return $user;
	}
	$ip      = vf_securite_ip();
	$blocage = ( '' !== (string) $username ) ? vf_securite_blocage_de( $ip ) : null;
	if ( $blocage ) {
		return new WP_Error( 'vf_securite_bloquee', vf_securite_message_blocage( $blocage ) );
	}
	if ( ! is_wp_error( $user ) || ! array_intersect( $user->get_error_codes(), array( 'invalid_username', 'invalid_email', 'incorrect_password' ) ) ) {
		return $user;
	}
	$r       = vf_securite_reglages();
	$restant = $r['tentatives'] - ( vf_securite_echecs( $ip ) + 1 );
	$message = '<strong>Identifiant ou mot de passe incorrect.</strong>';
	if ( '' !== $ip && ! vf_securite_de_confiance( $ip ) ) {
		if ( $restant <= 0 ) {
			$message .= sprintf( ' Trop de tentatives : la connexion est maintenant bloquée depuis votre adresse pendant %s.', 60 === $r['duree'] ? '1 heure' : $r['duree'] . ' minutes' );
		} else {
			$message .= sprintf( ' Il vous reste %s avant le blocage de votre adresse.', 1 === $restant ? '1 essai' : $restant . ' essais' );
		}
	}
	return new WP_Error( 'vf_securite_echec', $message );
}
add_filter( 'authenticate', 'vf_securite_message_d_echec', PHP_INT_MAX, 2 );

/**
 * Chaque échec de connexion est compté.
 *
 * @param string        $username Identifiant essayé.
 * @param WP_Error|null $erreur   Erreur renvoyée.
 */
function vf_securite_sur_echec( $username, $erreur = null ) {
	$ip = vf_securite_ip();
	if ( is_wp_error( $erreur ) && 'vf_securite_bloquee' === $erreur->get_error_code() ) {
		vf_securite_journal( 'refus', $ip, $username ); // Tentative pendant un blocage : refusée, non comptée.
		return;
	}
	vf_securite_compter_echec( $ip, $username );
}
add_action( 'wp_login_failed', 'vf_securite_sur_echec', 10, 2 );

/**
 * Une connexion réussie remet le compteur à zéro.
 */
function vf_securite_sur_reussite() {
	$ip = vf_securite_ip();
	if ( '' !== $ip ) {
		delete_transient( 'vf_sec_' . md5( $ip ) );
	}
}
add_action( 'wp_login', 'vf_securite_sur_reussite' );

/**
 * Le message de blocage est visible dès l'arrivée sur la page de connexion.
 *
 * @param string $message Message au-dessus du formulaire.
 * @return string
 */
function vf_securite_annonce_blocage( $message ) {
	$blocage = vf_securite_blocage_de( vf_securite_ip() );
	if ( $blocage && empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- simple affichage.
		$message .= '<div id="login_error" class="notice notice-error"><p>' . wp_kses( vf_securite_message_blocage( $blocage ), array( 'strong' => array() ) ) . '</p></div>';
	}
	return $message;
}
add_filter( 'login_message', 'vf_securite_annonce_blocage' );

/**
 * Le formulaire « tremble » aussi pour nos messages d'erreur, comme pour ceux de WordPress.
 *
 * @param string[] $codes Codes d'erreur.
 * @return string[]
 */
function vf_securite_codes_tremblement( $codes ) {
	return array_merge( $codes, array( 'vf_securite_echec', 'vf_securite_bloquee' ) );
}
add_filter( 'shake_error_codes', 'vf_securite_codes_tremblement' );

/* ---------- Autres portes d'entrée ---------- */

// XML-RPC : ancien accès à distance, inutile ici et cible habituelle des robots (essais de mots de passe en rafale).
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
remove_action( 'wp_head', 'rsd_link' );

/**
 * Mots de passe d'application (API REST) : les échecs sont comptés comme les autres.
 *
 * @param WP_Error $erreur Erreur d'authentification.
 */
function vf_securite_sur_echec_application( $erreur ) {
	vf_securite_compter_echec( vf_securite_ip(), 'mot de passe d’application' );
}
add_action( 'application_password_failed_authentication', 'vf_securite_sur_echec_application' );

/**
 * Une adresse bloquée ne peut pas non plus s'authentifier par mot de passe d'application.
 *
 * @param bool $disponible Fonction disponible ?
 * @return bool
 */
function vf_securite_application_si_non_bloquee( $disponible ) {
	return $disponible && ! vf_securite_blocage_de( vf_securite_ip() );
}
add_filter( 'wp_is_application_passwords_available', 'vf_securite_application_si_non_bloquee' );

/* ---------- Ne pas révéler les identifiants ---------- */

/**
 * La liste des comptes n'est pas publique dans l'API REST.
 *
 * @param array $routes Routes de l'API.
 * @return array
 */
function vf_securite_cacher_comptes_rest( $routes ) {
	if ( ! is_user_logged_in() ) {
		unset( $routes['/wp/v2/users'], $routes['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $routes;
}
add_filter( 'rest_endpoints', 'vf_securite_cacher_comptes_rest' );

/**
 * Les pages « auteur » (dont l'adresse contient l'identifiant de connexion) répondent « page introuvable ».
 */
function vf_securite_cacher_pages_auteur() {
	if ( is_author() || isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- simple lecture.
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'vf_securite_cacher_pages_auteur', 1 );

/**
 * Pas de liste des auteurs dans le plan de site XML.
 *
 * @param WP_Sitemaps_Provider $fournisseur Fournisseur.
 * @param string               $nom         Nom du fournisseur.
 * @return WP_Sitemaps_Provider|false
 */
function vf_securite_cacher_auteurs_sitemap( $fournisseur, $nom ) {
	return 'users' === $nom ? false : $fournisseur;
}
add_filter( 'wp_sitemaps_add_provider', 'vf_securite_cacher_auteurs_sitemap', 10, 2 );

/* ---------- Courriel et lien de déblocage ---------- */

/**
 * Jeton de déblocage : propre à l'adresse et au blocage, impossible à deviner sans les clés secrètes du site.
 *
 * @param string $ip      Adresse IP.
 * @param array  $blocage Blocage.
 * @return string
 */
function vf_securite_jeton( $ip, $blocage ) {
	return hash_hmac( 'sha256', $ip . '|' . (int) $blocage['debut'] . '|' . (int) $blocage['fin'], wp_salt( 'auth' ) );
}

/**
 * Prévient l'administrateur d'un blocage (cinq courriels par heure au plus).
 *
 * @param string $ip      Adresse IP.
 * @param array  $blocage Blocage.
 */
function vf_securite_prevenir( $ip, $blocage ) {
	$r = vf_securite_reglages();
	// Cinq courriels par heure au plus : assez pour les erreurs d'agents, sans inonder la boîte lors d'une attaque.
	$envoyes = (int) get_transient( 'vf_securite_courriels' );
	if ( ! $r['courriel'] || $envoyes >= 5 ) {
		return;
	}
	set_transient( 'vf_securite_courriels', $envoyes + 1, HOUR_IN_SECONDS );
	$lien  = add_query_arg(
		array(
			'vf_securite_debloquer' => md5( $ip ),
			'jeton'                 => vf_securite_jeton( $ip, $blocage ),
		),
		home_url( '/' )
	);
	$site  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$corps = "Une adresse IP vient d'être bloquée sur la page de connexion du site, après {$r['tentatives']} échecs.\n\n"
		. "Adresse IP : {$ip}\n"
		. 'Identifiant essayé : ' . ( $blocage['identifiant'] ? $blocage['identifiant'] : '(vide)' ) . "\n"
		. 'Blocage jusqu\'à : ' . wp_date( 'j F Y à H\hi', (int) $blocage['fin'] ) . "\n\n"
		. "S'il s'agit d'un agent qui s'est trompé de mot de passe, vous pouvez lever le blocage tout de suite :\n{$lien}\n\n"
		. "Si vous ne reconnaissez pas cette tentative, il n'y a rien à faire : le blocage se lève tout seul.\n"
		. "Pour limiter les messages, cinq courriels au plus sont envoyés par heure ; la liste complète des blocages est dans Réglages > Sécurité de la connexion.\n";
	wp_mail( get_option( 'admin_email' ), "[{$site}] Adresse IP bloquée sur la page de connexion", $corps );
}

/**
 * Lien de déblocage reçu par courriel : page de confirmation, puis déblocage.
 * La confirmation évite qu'un antivirus de messagerie, en ouvrant le lien, ne lève le blocage tout seul.
 */
function vf_securite_lien_de_deblocage() {
	if ( empty( $_GET['vf_securite_debloquer'] ) || empty( $_GET['jeton'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- protégé par le jeton signé.
		return;
	}
	$cle   = sanitize_key( wp_unslash( $_GET['vf_securite_debloquer'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$jeton = sanitize_key( wp_unslash( $_GET['jeton'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	nocache_headers();
	foreach ( vf_securite_blocages() as $ip => $blocage ) {
		if ( md5( $ip ) !== $cle || ! hash_equals( vf_securite_jeton( $ip, $blocage ), $jeton ) ) {
			continue;
		}
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			vf_securite_debloquer( $ip, 'lien reçu par courriel' );
			wp_die( '<h1>Adresse débloquée</h1><p>L’adresse ' . esc_html( $ip ) . ' peut de nouveau se connecter.</p>', 'Adresse débloquée', array( 'response' => 200 ) );
		}
		wp_die(
			'<h1>Lever le blocage ?</h1><p>L’adresse ' . esc_html( $ip ) . ' est bloquée sur la page de connexion jusqu’à ' . esc_html( wp_date( 'H\hi', (int) $blocage['fin'] ) ) . '.</p>'
			. '<form method="post"><p><button type="submit" class="button button-large">Débloquer cette adresse</button></p></form>',
			'Lever le blocage',
			array( 'response' => 200 )
		);
	}
	wp_die( '<h1>Lien expiré</h1><p>Ce blocage est déjà levé, ou le lien n’est plus valable.</p>', 'Lien expiré', array( 'response' => 410 ) );
}
add_action( 'init', 'vf_securite_lien_de_deblocage', 1 );
