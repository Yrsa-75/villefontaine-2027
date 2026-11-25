<?php
/**
 * Protection des comptes.
 *
 * - mots de passe solides imposés (longueur, variété, pas de mot de passe courant ni dérivé de l'identifiant) ;
 * - déconnexion automatique après une période sans activité ;
 * - courriel au titulaire du compte à chaque connexion depuis un nouvel appareil ;
 * - « mot de passe oublié » limité en nombre de demandes, et qui ne dit plus si un compte existe.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Mots de passe solides ---------- */

/**
 * Mots de passe les plus courants, et motifs à éviter (comparés en minuscules, sans accents).
 *
 * @return string[]
 */
function vf_securite_mdp_courants() {
	return array( 'motdepasse', 'password', 'azerty', 'qwerty', '123456', '654321', 'abcdef', 'admin', 'bonjour', 'soleil', 'doudou', 'loulou', 'chouchou', 'jetaime', 'iloveyou', 'welcome', 'bienvenue', 'letmein', 'mairie', 'villefontaine', 'isere', 'wordpress', 'changeme', 'secret', 'marseille', 'france' );
}

/**
 * Défauts d'un mot de passe au regard de la règle du site.
 *
 * Règle (dans l'esprit des recommandations de la CNIL) : 12 caractères au moins, avec au moins trois types
 * de caractères parmi majuscules, minuscules, chiffres et symboles ; une phrase de 20 caractères ou plus
 * est acceptée sans condition de variété.
 *
 * @param string $mdp         Mot de passe proposé.
 * @param string $identifiant Identifiant du compte.
 * @param string $courriel    Adresse de courriel du compte.
 * @return string[] Messages d'erreur ; vide si le mot de passe convient.
 */
function vf_securite_mdp_defauts( $mdp, $identifiant = '', $courriel = '' ) {
	$r       = vf_securite_reglages();
	$defauts = array();
	$mdp     = (string) $mdp;
	if ( ! $r['mdp_regles'] || vf_securite_en_pause() || '' === $mdp ) {
		return $defauts;
	}
	$longueur = mb_strlen( $mdp );
	if ( $longueur < $r['mdp_longueur'] ) {
		$defauts[] = sprintf( 'Le mot de passe doit comporter au moins %d caractères (il en compte %d).', $r['mdp_longueur'], $longueur );
	}
	$types = (int) preg_match( '/\p{Lu}/u', $mdp ) + (int) preg_match( '/\p{Ll}/u', $mdp ) + (int) preg_match( '/\d/', $mdp ) + (int) preg_match( '/[^\p{L}\d]/u', $mdp );
	if ( $types < 3 && $longueur < 20 ) {
		$defauts[] = 'Le mot de passe doit mélanger au moins trois types de caractères parmi : majuscules, minuscules, chiffres, symboles. Une phrase de 20 caractères ou plus convient aussi.';
	}
	if ( count( array_unique( mb_str_split( $mdp ) ) ) < 5 ) {
		$defauts[] = 'Le mot de passe répète trop souvent les mêmes caractères.';
	}

	$simple    = strtolower( remove_accents( $mdp ) );
	$interdits = array();
	foreach ( array( $identifiant, strstr( (string) $courriel, '@', true ) ) as $perso ) {
		$perso = strtolower( remove_accents( (string) $perso ) );
		if ( strlen( $perso ) >= 4 ) {
			$interdits[] = $perso;
		}
	}
	foreach ( $interdits as $perso ) {
		if ( false !== strpos( $simple, $perso ) ) {
			$defauts[] = 'Le mot de passe ne doit pas contenir votre identifiant ni le début de votre adresse de courriel.';
			break;
		}
	}
	// Mot courant, même « déguisé » par quelques chiffres ou symboles autour (Soleil2026!, Azerty123...).
	$coeur = preg_replace( '/[^a-z]/', '', $simple );
	foreach ( vf_securite_mdp_courants() as $courant ) {
		if ( $coeur === $courant || ( false !== strpos( $simple, $courant ) && $longueur < strlen( $courant ) + 8 ) ) {
			$defauts[] = 'Ce mot de passe repose sur un mot trop courant ou trop facile à deviner (« ' . $courant . ' »).';
			break;
		}
	}
	return $defauts;
}

/**
 * Création ou modification d'un compte dans l'administration.
 *
 * @param WP_Error $erreurs  Erreurs du formulaire.
 * @param bool     $maj      Mise à jour d'un compte existant ?
 * @param stdClass $compte   Données du compte.
 */
function vf_securite_mdp_profil( $erreurs, $maj, $compte ) {
	if ( empty( $compte->user_pass ) ) {
		return; // Mot de passe inchangé.
	}
	foreach ( vf_securite_mdp_defauts( $compte->user_pass, $compte->user_login ?? '', $compte->user_email ?? '' ) as $defaut ) {
		$erreurs->add( 'pass', '<strong>Mot de passe refusé :</strong> ' . esc_html( $defaut ), array( 'form-field' => 'pass1' ) );
	}
}
add_action( 'user_profile_update_errors', 'vf_securite_mdp_profil', 10, 3 );

/**
 * Nouveau mot de passe choisi après « mot de passe oublié ».
 *
 * @param WP_Error         $erreurs Erreurs du formulaire.
 * @param WP_User|WP_Error $compte  Compte concerné.
 */
function vf_securite_mdp_reinitialisation( $erreurs, $compte ) {
	// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- WordPress a déjà vérifié la clé de réinitialisation ; un mot de passe ne se nettoie pas.
	if ( ! ( $compte instanceof WP_User ) || empty( $_POST['pass1'] ) ) {
		return;
	}
	foreach ( vf_securite_mdp_defauts( (string) wp_unslash( $_POST['pass1'] ), $compte->user_login, $compte->user_email ) as $defaut ) {
		$erreurs->add( 'vf_securite_mdp', '<strong>Mot de passe refusé :</strong> ' . esc_html( $defaut ) );
	}
	// phpcs:enable
}
add_action( 'validate_password_reset', 'vf_securite_mdp_reinitialisation', 10, 2 );

/**
 * Création ou modification d'un compte par l'API (éditeur, applications).
 *
 * @param stdClass        $compte  Données préparées.
 * @param WP_REST_Request $requete Requête.
 * @return stdClass|WP_Error
 */
function vf_securite_mdp_api( $compte, $requete ) {
	if ( empty( $compte->user_pass ) ) {
		return $compte;
	}
	$existant = ! empty( $compte->ID ) ? get_userdata( $compte->ID ) : null;
	$defauts  = vf_securite_mdp_defauts( $compte->user_pass, $compte->user_login ?? ( $existant->user_login ?? '' ), $compte->user_email ?? ( $existant->user_email ?? '' ) );
	return $defauts ? new WP_Error( 'vf_securite_mdp', implode( ' ', $defauts ), array( 'status' => 400 ) ) : $compte;
}
add_filter( 'rest_pre_insert_user', 'vf_securite_mdp_api', 10, 2 );

/**
 * La règle est rappelée sous le champ du mot de passe.
 *
 * @param string $conseil Conseil d'origine de WordPress.
 * @return string
 */
function vf_securite_mdp_conseil( $conseil ) {
	$r = vf_securite_reglages();
	if ( ! $r['mdp_regles'] || vf_securite_en_pause() ) {
		return $conseil;
	}
	return sprintf( 'Règle du site : au moins %d caractères, avec au moins trois types parmi majuscules, minuscules, chiffres et symboles. Une phrase de 20 caractères ou plus convient aussi. Évitez votre identifiant, le nom de la ville et les mots courants.', $r['mdp_longueur'] );
}
add_filter( 'password_hint', 'vf_securite_mdp_conseil' );

/* ---------- Déconnexion après inactivité ---------- */

/**
 * Ferme la session d'une personne restée trop longtemps sans activité.
 *
 * L'heure de la dernière action est gardée dans la session de WordPress (mise à jour une fois par minute au plus).
 * Les « battements » automatiques de l'administration ne comptent pas comme une activité : sans cela,
 * un onglet oublié ouvert garderait la session vivante indéfiniment.
 */
function vf_securite_inactivite() {
	$minutes = vf_securite_reglages()['inactivite'];
	// Seules les sessions ouvertes par la page de connexion (témoin de connexion) sont concernées. Sans ce témoin, on ne
	// demande surtout pas « qui est connecté ? » si tôt : cela empêcherait l'authentification par mot de passe d'application,
	// que WordPress ne résout qu'un peu plus tard, une fois la demande reconnue comme un appel à l'API.
	if ( ! $minutes || vf_securite_en_pause() || ! defined( 'LOGGED_IN_COOKIE' ) || empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) || ! is_user_logged_in() ) {
		return;
	}
	$jeton = wp_get_session_token();
	if ( ! $jeton ) {
		return; // Authentification sans session (mot de passe d'application).
	}
	$sessions = WP_Session_Tokens::get_instance( get_current_user_id() );
	$session  = $sessions->get( $jeton );
	if ( ! is_array( $session ) ) {
		return;
	}
	$instant = time();
	if ( empty( $session['vf_vu'] ) ) {
		// Session ouverte avant l'installation de cette protection (ou toute nouvelle) : le décompte part de maintenant.
		$session['vf_vu'] = $instant;
		$sessions->update( $jeton, $session );
		return;
	}
	$vu = (int) $session['vf_vu'];
	// phpcs:ignore WordPress.Security.NonceVerification -- simple lecture du nom de l'action.
	$battement = wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'heartbeat' === $_REQUEST['action'];

	if ( $instant - $vu > $minutes * MINUTE_IN_SECONDS ) {
		vf_securite_journal( 'inactivite', vf_securite_ip(), 'après ' . vf_securite_duree_en_clair( $minutes ) );
		$sessions->destroy( $jeton );
		wp_clear_auth_cookie();
		wp_set_current_user( 0 );
		// Dans l'administration, on renvoie vers la connexion avec une explication ; l'éditeur, lui, affiche sa propre fenêtre de reconnexion.
		if ( is_admin() && ! wp_doing_ajax() && ! headers_sent() ) {
			$retour = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : admin_url(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			wp_safe_redirect( add_query_arg( 'vf_inactif', 1, wp_login_url( $retour ) ) );
			exit;
		}
		return;
	}
	if ( ! $battement && $instant - $vu >= MINUTE_IN_SECONDS ) {
		$session['vf_vu'] = $instant;
		$sessions->update( $jeton, $session );
	}
}
add_action( 'init', 'vf_securite_inactivite', 2 );

/**
 * Explication affichée sur la page de connexion après une déconnexion automatique.
 *
 * @param string $message Message au-dessus du formulaire.
 * @return string
 */
function vf_securite_message_inactivite( $message ) {
	if ( isset( $_GET['vf_inactif'] ) && empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- simple affichage.
		$message .= '<p class="message">Par sécurité, vous avez été déconnecté après ' . esc_html( vf_securite_duree_en_clair( max( 15, vf_securite_reglages()['inactivite'] ) ) ) . ' sans activité. Reconnectez-vous pour reprendre là où vous en étiez.</p>';
	}
	return $message;
}
add_filter( 'login_message', 'vf_securite_message_inactivite' );

/* ---------- Connexion depuis un nouvel appareil ---------- */

/**
 * Nom du témoin (cookie) qui identifie un appareil déjà utilisé pour se connecter.
 * Il ne contient qu'un numéro tiré au hasard, sert uniquement à la sécurité du compte,
 * et n'est déposé qu'au moment d'une connexion à l'administration.
 *
 * @return string
 */
function vf_securite_temoin_appareil() {
	return 'vf_appareil_' . ( defined( 'COOKIEHASH' ) ? COOKIEHASH : md5( home_url() ) );
}

/**
 * Navigateur et système décrits en clair, d'après la signature envoyée par le navigateur.
 *
 * @return string
 */
function vf_securite_navigateur() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	if ( '' === $ua ) {
		return 'navigateur inconnu';
	}
	$navigateur = 'navigateur inconnu';
	foreach ( array(
		'Edg/'     => 'Edge',
		'OPR/'     => 'Opera',
		'Firefox/' => 'Firefox',
		'Chrome/'  => 'Chrome',
		'Safari/'  => 'Safari',
	) as $signe => $nom ) {
		if ( false !== strpos( $ua, $signe ) ) {
			$navigateur = $nom;
			break;
		}
	}
	$systeme = '';
	foreach ( array(
		'Windows'   => 'Windows',
		'iPhone'    => 'iPhone',
		'iPad'      => 'iPad',
		'Android'   => 'Android',
		'Macintosh' => 'Mac',
		'Linux'     => 'Linux',
	) as $signe => $nom ) {
		if ( false !== strpos( $ua, $signe ) ) {
			$systeme = $nom;
			break;
		}
	}
	return $navigateur . ( $systeme ? ' sur ' . $systeme : '' );
}

/**
 * À chaque connexion réussie : l'appareil est-il connu pour ce compte ? Sinon, le titulaire est prévenu.
 *
 * @param string  $identifiant Identifiant.
 * @param WP_User $compte      Compte.
 */
function vf_securite_nouvel_appareil( $identifiant, $compte = null ) {
	if ( ! ( $compte instanceof WP_User ) || vf_securite_en_pause() ) {
		return;
	}
	$nom     = vf_securite_temoin_appareil();
	$connus  = get_user_meta( $compte->ID, 'vf_securite_appareils', true );
	$connus  = is_array( $connus ) ? $connus : array();
	$valeur  = isset( $_COOKIE[ $nom ] ) ? preg_replace( '/[^a-f0-9]/', '', (string) wp_unslash( $_COOKIE[ $nom ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$nouveau = ( 32 !== strlen( $valeur ) || ! isset( $connus[ hash( 'sha256', $valeur ) ] ) );

	if ( 32 !== strlen( $valeur ) ) {
		$valeur = bin2hex( random_bytes( 16 ) );
	}
	// Seule une empreinte du numéro est gardée dans la base ; dix appareils au plus par compte, les plus récents.
	$connus[ hash( 'sha256', $valeur ) ] = time();
	arsort( $connus );
	update_user_meta( $compte->ID, 'vf_securite_appareils', array_slice( $connus, 0, 10, true ) );
	if ( ! headers_sent() ) {
		setcookie(
			$nom,
			$valeur,
			array(
				'expires'  => time() + YEAR_IN_SECONDS,
				'path'     => defined( 'SITECOOKIEPATH' ) ? SITECOOKIEPATH : '/',
				'domain'   => defined( 'COOKIE_DOMAIN' ) ? (string) COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}
	if ( ! $nouveau ) {
		return;
	}
	$ip = vf_securite_ip();
	vf_securite_journal( 'nouvel_appareil', $ip, vf_securite_navigateur(), $identifiant );
	if ( ! vf_securite_reglages()['alerte_appareil'] ) {
		return;
	}
	$site  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$corps = "Bonjour,\n\n"
		. "Une connexion à votre compte « {$identifiant} » vient d'avoir lieu sur l'administration du site {$site}, depuis un appareil ou un navigateur qui n'avait pas encore été utilisé avec ce compte.\n\n"
		. 'Date : ' . wp_date( 'j F Y à H\hi' ) . "\n"
		. 'Appareil : ' . vf_securite_navigateur() . "\n"
		. 'Adresse IP : ' . ( $ip ? $ip : 'inconnue' ) . "\n\n"
		. "C'était vous ? Il n'y a rien à faire. Ce message est aussi envoyé après un changement de navigateur ou un effacement des cookies.\n\n"
		. "Ce n'était pas vous ? Changez votre mot de passe sans attendre, puis prévenez l'administrateur du site :\n"
		. vf_securite_url_connexion() . "?action=lostpassword\n";
	wp_mail( $compte->user_email, "[{$site}] Connexion à votre compte depuis un nouvel appareil", $corps );
}
add_action( 'wp_login', 'vf_securite_nouvel_appareil', 20, 2 );

/* ---------- Mot de passe oublié ---------- */

/**
 * Demande « mot de passe oublié » : limitée à trois par heure et par adresse IP, et la réponse est
 * la même que le compte existe ou non (sinon la page permet de deviner les identifiants).
 */
function vf_securite_mdp_oublie() {
	if ( vf_securite_en_pause() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification -- formulaire public de WordPress, sans jeton ; la demande est limitée en nombre.
	$saisi = isset( $_POST['user_login'] ) && is_string( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';
	// phpcs:enable
	$ip     = vf_securite_ip();
	$cle    = 'vf_secm_' . md5( $ip );
	$nombre = (int) get_transient( $cle );

	if ( '' !== $saisi ) {
		if ( $nombre >= 3 || vf_securite_blocage_de( $ip ) ) {
			vf_securite_journal( 'mdp_refus', $ip, $saisi, ' ' );
		} else {
			set_transient( $cle, $nombre + 1, HOUR_IN_SECONDS );
			$resultat = retrieve_password(); // Lit lui-même la saisie du formulaire (identifiant ou adresse de courriel).
			$note     = $saisi;
			if ( is_wp_error( $resultat ) && 'retrieve_password_email_failure' === $resultat->get_error_code() ) {
				$note .= ' (le courriel n’a pas pu être envoyé : vérifier la configuration de l’envoi des courriels)';
			}
			vf_securite_journal( 'mdp_demande', $ip, $note, ' ' );
		}
	}
	wp_safe_redirect( add_query_arg( 'checkemail', 'confirm', wp_login_url() ) );
	exit;
}
add_action( 'login_form_lostpassword', 'vf_securite_mdp_oublie' );
add_action( 'login_form_retrievepassword', 'vf_securite_mdp_oublie' );

/**
 * Message affiché après la demande : identique dans tous les cas.
 *
 * @param WP_Error $erreurs Messages de la page de connexion.
 * @return WP_Error
 */
function vf_securite_mdp_oublie_message( $erreurs ) {
	if ( $erreurs instanceof WP_Error && ! vf_securite_en_pause() && in_array( 'confirm', $erreurs->get_error_codes(), true ) ) {
		$erreurs->remove( 'confirm' );
		$erreurs->add( 'confirm', 'Si un compte correspond à cet identifiant ou à cette adresse, un courriel contenant un lien de réinitialisation vient de lui être envoyé. Pensez à regarder dans les courriers indésirables.', 'message' );
	}
	return $erreurs;
}
add_filter( 'wp_login_errors', 'vf_securite_mdp_oublie_message' );
