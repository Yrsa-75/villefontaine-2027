<?php
/**
 * Journal de sécurité et d'activité.
 *
 * Une table dédiée garde la trace des connexions (réussies, échouées, bloquées) et des actions
 * sensibles de l'administration : comptes créés ou supprimés, rôles modifiés, mots de passe changés,
 * extensions installées ou activées, réglages importants modifiés.
 *
 * Données conservées : date, type d'événement, adresse IP, compte concerné, précision.
 * Durée de conservation réglable (180 jours par défaut), purge quotidienne automatique.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nom de la table du journal.
 *
 * @return string
 */
function vf_securite_table() {
	global $wpdb;
	return $wpdb->prefix . 'vf_securite_journal';
}

/**
 * Crée ou met à jour la table, et y reprend l'ancien journal (version 1.0, stocké dans une option).
 */
function vf_securite_journal_installer() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table = vf_securite_table();
	$jeu   = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quand int(10) unsigned NOT NULL DEFAULT 0,
			type varchar(30) NOT NULL DEFAULT '',
			ip varchar(45) NOT NULL DEFAULT '',
			compte varchar(60) NOT NULL DEFAULT '',
			detail varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY quand (quand),
			KEY type (type)
		) {$jeu};"
	);

	$ancien = get_option( 'vf_securite_journal' );
	if ( is_array( $ancien ) && $ancien ) {
		foreach ( $ancien as $l ) {
			if ( is_array( $l ) && isset( $l[0], $l[1], $l[2] ) ) {
				$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$table,
					array(
						'quand'  => (int) $l[0],
						'type'   => sanitize_key( $l[1] ),
						'ip'     => (string) $l[2],
						'detail' => (string) ( $l[3] ?? '' ),
					),
					array( '%d', '%s', '%s', '%s' )
				);
			}
		}
	}
	delete_option( 'vf_securite_journal' );

	if ( ! wp_next_scheduled( 'vf_securite_purge' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'vf_securite_purge' );
	}
}

/**
 * Types d'événements : libellé affiché, puis famille (pour le filtre du journal).
 *
 * @return array
 */
function vf_securite_types_evenements() {
	return array(
		'connexion'       => array( 'Connexion réussie', 'connexions' ),
		'deconnexion'     => array( 'Déconnexion', 'connexions' ),
		'inactivite'      => array( 'Déconnexion automatique (inactivité)', 'connexions' ),
		'nouvel_appareil' => array( 'Connexion depuis un nouvel appareil', 'connexions' ),
		'echec'           => array( 'Échec de connexion', 'connexions' ),
		'blocage'         => array( 'Adresse bloquée', 'connexions' ),
		'refus'           => array( 'Tentative pendant un blocage', 'connexions' ),
		'deblocage'       => array( 'Adresse débloquée', 'connexions' ),
		'mdp_demande'     => array( 'Demande « mot de passe oublié »', 'comptes' ),
		'mdp_refus'       => array( 'Demande « mot de passe oublié » refusée (trop de demandes)', 'comptes' ),
		'mdp_change'      => array( 'Mot de passe modifié', 'comptes' ),
		'compte_cree'     => array( 'Compte créé', 'comptes' ),
		'compte_supprime' => array( 'Compte supprimé', 'comptes' ),
		'role'            => array( 'Rôle modifié', 'comptes' ),
		'courriel_compte' => array( 'Adresse de courriel d’un compte modifiée', 'comptes' ),
		'ext_activee'     => array( 'Extension activée', 'site' ),
		'ext_desactivee'  => array( 'Extension désactivée', 'site' ),
		'ext_supprimee'   => array( 'Extension supprimée', 'site' ),
		'installation'    => array( 'Installation ou mise à jour', 'site' ),
		'theme'           => array( 'Thème changé', 'site' ),
		'reglage'         => array( 'Réglage modifié', 'site' ),
		'fichiers'        => array( 'Règles de protection des fichiers', 'site' ),
	);
}

/**
 * Familles d'événements proposées dans le filtre.
 *
 * @return array
 */
function vf_securite_familles() {
	return array(
		''           => 'Tout',
		'connexions' => 'Connexions',
		'comptes'    => 'Comptes',
		'site'       => 'Extensions et réglages',
	);
}

/**
 * Ajoute une ligne au journal.
 *
 * @param string $type   Clé de vf_securite_types_evenements().
 * @param string $ip     Adresse IP à l'origine de l'événement.
 * @param string $detail Identifiant essayé, ou précision.
 * @param string $compte Compte qui agit (identifiant) ; par défaut, la personne connectée.
 */
function vf_securite_journal( $type, $ip, $detail = '', $compte = '' ) {
	global $wpdb;
	if ( ! get_option( 'vf_securite_version' ) ) {
		return; // Table pas encore créée (toute première requête après l'installation).
	}
	if ( '' === $compte && function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
		$compte = wp_get_current_user()->user_login;
	}
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		vf_securite_table(),
		array(
			'quand'  => time(),
			'type'   => sanitize_key( $type ),
			'ip'     => mb_substr( (string) $ip, 0, 45 ),
			'compte' => mb_substr( sanitize_text_field( (string) $compte ), 0, 60 ),
			'detail' => mb_substr( sanitize_text_field( (string) $detail ), 0, 255 ),
		),
		array( '%d', '%s', '%s', '%s', '%s' )
	);
}

/**
 * Lit le journal, du plus récent au plus ancien.
 *
 * @param string $famille Famille d'événements ('' = tout).
 * @param int    $page    Page demandée (à partir de 1).
 * @param int    $par     Lignes par page.
 * @return array [ lignes, total ].
 */
function vf_securite_journal_lire( $famille = '', $page = 1, $par = 50 ) {
	global $wpdb;
	$table = vf_securite_table();
	$ou    = '1=1';
	if ( '' !== $famille ) {
		$types = array();
		foreach ( vf_securite_types_evenements() as $cle => $t ) {
			if ( $t[1] === $famille ) {
				$types[] = "'" . esc_sql( $cle ) . "'";
			}
		}
		$ou = $types ? 'type IN (' . implode( ',', $types ) . ')' : '1=0';
	}
	$debut = max( 0, ( (int) $page - 1 ) * (int) $par );
	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL -- nom de table et liste de types construits ici, sans donnée saisie.
	$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$ou}" );
	$lignes = $wpdb->get_results( $wpdb->prepare( "SELECT quand, type, ip, compte, detail FROM {$table} WHERE {$ou} ORDER BY id DESC LIMIT %d, %d", $debut, (int) $par ), ARRAY_A );
	// phpcs:enable
	return array( is_array( $lignes ) ? $lignes : array(), $total );
}

/**
 * Purge quotidienne : les lignes plus anciennes que la durée de conservation sont supprimées.
 */
function vf_securite_purger() {
	global $wpdb;
	$table  = vf_securite_table();
	$limite = time() - vf_securite_reglages()['conservation'] * DAY_IN_SECONDS;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE quand < %d", $limite ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
add_action( 'vf_securite_purge', 'vf_securite_purger' );

/* ---------- Activité de l'administration ---------- */

/**
 * Connexion réussie.
 *
 * @param string  $identifiant Identifiant.
 * @param WP_User $compte      Compte.
 */
function vf_securite_noter_connexion( $identifiant, $compte = null ) {
	$roles = ( $compte instanceof WP_User ) ? implode( ', ', array_map( 'vf_securite_nom_role', $compte->roles ) ) : '';
	vf_securite_journal( 'connexion', vf_securite_ip(), $roles, $identifiant );
}
add_action( 'wp_login', 'vf_securite_noter_connexion', 10, 2 );

/**
 * Déconnexion volontaire.
 *
 * @param int $id Identifiant numérique du compte.
 */
function vf_securite_noter_deconnexion( $id = 0 ) {
	$compte = $id ? get_userdata( $id ) : null;
	if ( $compte ) {
		vf_securite_journal( 'deconnexion', vf_securite_ip(), '', $compte->user_login );
	}
}
add_action( 'wp_logout', 'vf_securite_noter_deconnexion' );

/**
 * Nom d'un rôle en français.
 *
 * @param string $role Clé du rôle.
 * @return string
 */
function vf_securite_nom_role( $role ) {
	$roles = wp_roles()->roles;
	return isset( $roles[ $role ]['name'] ) ? translate_user_role( $roles[ $role ]['name'] ) : (string) $role;
}

/**
 * Compte créé.
 *
 * @param int $id Identifiant numérique du compte.
 */
function vf_securite_noter_compte_cree( $id ) {
	$compte = get_userdata( $id );
	if ( $compte ) {
		vf_securite_journal( 'compte_cree', vf_securite_ip(), $compte->user_login . ' (' . implode( ', ', array_map( 'vf_securite_nom_role', $compte->roles ) ) . ')' );
	}
}
add_action( 'user_register', 'vf_securite_noter_compte_cree' );

/**
 * Compte supprimé.
 *
 * @param int $id Identifiant numérique du compte.
 */
function vf_securite_noter_compte_supprime( $id ) {
	$compte = get_userdata( $id );
	if ( $compte ) {
		vf_securite_journal( 'compte_supprime', vf_securite_ip(), $compte->user_login );
	}
}
add_action( 'delete_user', 'vf_securite_noter_compte_supprime' );

/**
 * Rôle modifié.
 *
 * @param int      $id      Identifiant numérique du compte.
 * @param string   $role    Nouveau rôle.
 * @param string[] $anciens Anciens rôles.
 */
function vf_securite_noter_role( $id, $role, $anciens = array() ) {
	$compte = get_userdata( $id );
	if ( ! $compte || ! $anciens || array( $role ) === array_values( (array) $anciens ) ) {
		return; // À la création du compte, il n'y a pas d'ancien rôle : l'événement « compte créé » suffit.
	}
	vf_securite_journal( 'role', vf_securite_ip(), $compte->user_login . ' : ' . implode( ', ', array_map( 'vf_securite_nom_role', (array) $anciens ) ) . ' → ' . vf_securite_nom_role( $role ) );
}
add_action( 'set_user_role', 'vf_securite_noter_role', 10, 3 );

/**
 * Profil modifié : mot de passe ou adresse de courriel.
 *
 * @param int     $id    Identifiant numérique du compte.
 * @param WP_User $avant Compte avant la modification.
 */
function vf_securite_noter_profil( $id, $avant ) {
	$apres = get_userdata( $id );
	if ( ! $apres || ! ( $avant instanceof WP_User ) ) {
		return;
	}
	if ( $apres->user_pass !== $avant->user_pass ) {
		vf_securite_journal( 'mdp_change', vf_securite_ip(), $apres->user_login );
	}
	if ( $apres->user_email !== $avant->user_email ) {
		vf_securite_journal( 'courriel_compte', vf_securite_ip(), $apres->user_login );
	}
}
add_action( 'profile_update', 'vf_securite_noter_profil', 10, 2 );

/**
 * Mot de passe réinitialisé par le lien reçu par courriel.
 *
 * @param WP_User $compte Compte.
 */
function vf_securite_noter_reinitialisation( $compte ) {
	if ( $compte instanceof WP_User ) {
		vf_securite_journal( 'mdp_change', vf_securite_ip(), $compte->user_login . ' (lien « mot de passe oublié »)', $compte->user_login );
	}
}
add_action( 'after_password_reset', 'vf_securite_noter_reinitialisation' );

/**
 * Nom lisible d'une extension à partir de son fichier principal.
 *
 * @param string $fichier Chemin relatif : dossier/fichier.php.
 * @return string
 */
function vf_securite_nom_extension( $fichier ) {
	$chemin = WP_PLUGIN_DIR . '/' . $fichier;
	if ( is_readable( $chemin ) ) {
		$infos = get_file_data( $chemin, array( 'nom' => 'Plugin Name', 'version' => 'Version' ) );
		if ( ! empty( $infos['nom'] ) ) {
			return $infos['nom'] . ( $infos['version'] ? ' ' . $infos['version'] : '' );
		}
	}
	return (string) $fichier;
}

/**
 * Extension activée.
 *
 * @param string $fichier Fichier principal de l'extension.
 */
function vf_securite_noter_activation( $fichier ) {
	vf_securite_journal( 'ext_activee', vf_securite_ip(), vf_securite_nom_extension( $fichier ) );
}
add_action( 'activated_plugin', 'vf_securite_noter_activation' );

/**
 * Extension désactivée.
 *
 * @param string $fichier Fichier principal de l'extension.
 */
function vf_securite_noter_desactivation( $fichier ) {
	vf_securite_journal( 'ext_desactivee', vf_securite_ip(), vf_securite_nom_extension( $fichier ) );
}
add_action( 'deactivated_plugin', 'vf_securite_noter_desactivation' );

/**
 * Extension supprimée.
 *
 * @param string $fichier  Fichier principal de l'extension.
 * @param bool   $supprime Suppression réussie ?
 */
function vf_securite_noter_suppression( $fichier, $supprime = true ) {
	if ( $supprime ) {
		vf_securite_journal( 'ext_supprimee', vf_securite_ip(), (string) $fichier );
	}
}
add_action( 'deleted_plugin', 'vf_securite_noter_suppression', 10, 2 );

/**
 * Installation ou mise à jour d'une extension, d'un thème ou de WordPress.
 *
 * @param object $outil   Outil de mise à jour de WordPress.
 * @param array  $donnees Type (plugin, theme, core) et action (install, update).
 */
function vf_securite_noter_installation( $outil, $donnees ) {
	$types   = array(
		'plugin'      => 'extension',
		'theme'       => 'thème',
		'core'        => 'WordPress',
		'translation' => '',
	);
	$type    = $types[ $donnees['type'] ?? '' ] ?? '';
	$actions = array(
		'install' => 'Installation',
		'update'  => 'Mise à jour',
	);
	if ( '' === $type || empty( $actions[ $donnees['action'] ?? '' ] ) ) {
		return; // Les mises à jour de traductions ne sont pas notées.
	}
	$quoi = array();
	foreach ( (array) ( $donnees['plugins'] ?? array() ) as $fichier ) {
		$quoi[] = vf_securite_nom_extension( $fichier );
	}
	foreach ( (array) ( $donnees['themes'] ?? array() ) as $theme ) {
		$quoi[] = (string) $theme;
	}
	if ( ! $quoi && ! empty( $outil->result['destination_name'] ) ) {
		$quoi[] = (string) $outil->result['destination_name'];
	}
	if ( 'core' === ( $donnees['type'] ?? '' ) ) {
		$quoi = array( 'version ' . get_bloginfo( 'version' ) );
	}
	vf_securite_journal( 'installation', vf_securite_ip(), $actions[ $donnees['action'] ] . ' : ' . $type . ' ' . implode( ', ', $quoi ) );
}
add_action( 'upgrader_process_complete', 'vf_securite_noter_installation', 10, 2 );

/**
 * Thème changé.
 *
 * @param string $nom Nom du nouveau thème.
 */
function vf_securite_noter_theme( $nom ) {
	vf_securite_journal( 'theme', vf_securite_ip(), (string) $nom );
}
add_action( 'switch_theme', 'vf_securite_noter_theme' );

/**
 * Réglages surveillés : ceux dont la modification change la sécurité ou l'identité du site.
 *
 * @return array option => libellé.
 */
function vf_securite_reglages_surveilles() {
	return array(
		'siteurl'              => 'Adresse web de WordPress',
		'home'                 => 'Adresse web du site',
		'blogname'             => 'Titre du site',
		'admin_email'          => 'Adresse de courriel d’administration',
		'users_can_register'   => 'Inscription ouverte à tous',
		'default_role'         => 'Rôle par défaut des nouveaux comptes',
		'permalink_structure'  => 'Structure des adresses',
		'blog_public'          => 'Visibilité par les moteurs de recherche',
		'vf_securite_reglages' => 'Réglages de sécurité',
	);
}

/**
 * Réglage important modifié.
 *
 * @param string $option Nom de l'option.
 * @param mixed  $avant  Ancienne valeur.
 * @param mixed  $apres  Nouvelle valeur.
 */
function vf_securite_noter_reglage( $option, $avant, $apres ) {
	$surveilles = vf_securite_reglages_surveilles();
	if ( ! isset( $surveilles[ $option ] ) || $avant === $apres ) { // phpcs:ignore Universal.Operators.StrictComparisons
		return;
	}
	$detail = $surveilles[ $option ];
	if ( is_scalar( $avant ) && is_scalar( $apres ) ) {
		$detail .= ' : « ' . $avant . ' » → « ' . $apres . ' »';
	} elseif ( is_array( $avant ) && is_array( $apres ) ) {
		$changes = array();
		foreach ( $apres as $cle => $valeur ) {
			if ( ! array_key_exists( $cle, $avant ) || $avant[ $cle ] !== $valeur ) { // phpcs:ignore Universal.Operators.StrictComparisons
				$changes[] = $cle;
			}
		}
		$detail .= $changes ? ' (' . implode( ', ', $changes ) . ')' : '';
	}
	vf_securite_journal( 'reglage', vf_securite_ip(), $detail );
}
add_action( 'updated_option', 'vf_securite_noter_reglage', 10, 3 );
