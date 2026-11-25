<?php
/**
 * Administration : menu « Sécurité ».
 * Réservé aux administrateurs ; chaque action est protégée par un jeton anti-falsification.
 *
 * Six onglets : vue d'ensemble, connexion, comptes, protection du site, page de connexion, journal.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Onglets de la page.
 *
 * @return array clé => libellé.
 */
function vf_securite_onglets() {
	return array(
		'etat'      => 'Vue d’ensemble',
		'connexion' => 'Connexion',
		'comptes'   => 'Comptes',
		'site'      => 'Protection du site',
		'page'      => 'Page de connexion',
		'journal'   => 'Journal',
	);
}

/**
 * Adresse d'un onglet.
 *
 * @param string $onglet Clé de l'onglet.
 * @param array  $plus   Paramètres supplémentaires.
 * @return string
 */
function vf_securite_url_admin( $onglet = 'etat', $plus = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'vf-securite', 'onglet' => $onglet ), $plus ), admin_url( 'admin.php' ) );
}

/**
 * Onglet demandé.
 *
 * @return string
 */
function vf_securite_onglet_courant() {
	$onglet = isset( $_REQUEST['onglet'] ) ? sanitize_key( wp_unslash( $_REQUEST['onglet'] ) ) : 'etat'; // phpcs:ignore WordPress.Security.NonceVerification -- simple navigation.
	return isset( vf_securite_onglets()[ $onglet ] ) ? $onglet : 'etat';
}

/**
 * Ajoute le menu.
 */
function vf_securite_menu() {
	$page = add_menu_page( 'Sécurité', 'Sécurité', 'manage_options', 'vf-securite', 'vf_securite_page', 'dashicons-shield', 81 );
	add_action( 'load-' . $page, 'vf_securite_traiter' );
	add_action( 'load-' . $page, 'vf_securite_preparer_page' );
}
add_action( 'admin_menu', 'vf_securite_menu' );

/**
 * Lien « Réglages » dans la liste des extensions.
 *
 * @param string[] $liens Liens d'action.
 * @return string[]
 */
function vf_securite_lien_reglages( $liens ) {
	array_unshift( $liens, '<a href="' . esc_url( vf_securite_url_admin() ) . '">Réglages</a>' );
	return $liens;
}
add_filter( 'plugin_action_links_securite-villefontaine/securite-villefontaine.php', 'vf_securite_lien_reglages' );

/**
 * Après l'activation : rappel de l'adresse de connexion.
 */
function vf_securite_bienvenue() {
	if ( ! current_user_can( 'manage_options' ) || ! get_transient( 'vf_securite_bienvenue' ) ) {
		return;
	}
	delete_transient( 'vf_securite_bienvenue' );
	echo '<div class="notice notice-warning"><p><strong>Sécurité Villefontaine est active.</strong> La page de connexion est à l’adresse <code>' . esc_html( vf_securite_url_connexion() ) . '</code> : notez-la et transmettez-la aux agents. Les adresses wp-login.php et wp-admin ne répondent plus aux visiteurs non connectés. <a href="' . esc_url( vf_securite_url_admin() ) . '">Voir les protections</a></p></div>';
}
add_action( 'admin_notices', 'vf_securite_bienvenue' );

/**
 * Message à afficher après une action (conservé le temps de la redirection).
 *
 * @param string $texte Message.
 * @param string $type  success | error.
 */
function vf_securite_message( $texte, $type = 'success' ) {
	set_transient( 'vf_securite_message_' . get_current_user_id(), array( $texte, $type ), 60 );
}

/**
 * Sélecteur de photo de la médiathèque, pour l'onglet « Page de connexion ».
 */
function vf_securite_preparer_page() {
	if ( 'page' === vf_securite_onglet_courant() ) {
		add_action(
			'admin_enqueue_scripts',
			static function () {
				wp_enqueue_media();
			}
		);
	}
}

/**
 * Case cochée dans le formulaire envoyé ?
 *
 * @param string $nom Nom du champ.
 * @return int 1 ou 0.
 */
function vf_securite_case( $nom ) {
	return empty( $_POST[ $nom ] ) ? 0 : 1; // phpcs:ignore WordPress.Security.NonceVerification -- jeton vérifié par vf_securite_traiter().
}

/**
 * Traite les formulaires de la page.
 */
function vf_securite_traiter() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'vf_securite' );
	$action = isset( $_POST['vf_action'] ) ? sanitize_key( wp_unslash( $_POST['vf_action'] ) ) : '';
	$onglet = vf_securite_onglet_courant();
	$qui    = 'administrateur ' . wp_get_current_user()->user_login;

	if ( 'debloquer' === $action ) {
		$ip = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
		vf_securite_message( vf_securite_debloquer( $ip, $qui ) ? 'L’adresse ' . $ip . ' est débloquée.' : 'Cette adresse n’était plus bloquée.' );
	} elseif ( 'tout-debloquer' === $action ) {
		foreach ( array_keys( vf_securite_blocages() ) as $ip ) {
			vf_securite_debloquer( $ip, $qui );
		}
		vf_securite_message( 'Toutes les adresses sont débloquées.' );
	} elseif ( 'fichiers' === $action ) {
		$etat = vf_securite_htaccess_ecrire( false, true );
		vf_securite_message( $etat['message'], 'actif' === $etat['etat'] ? 'success' : 'error' );
	} elseif ( 'reglages' === $action ) {
		$avant   = vf_securite_reglages();
		$r       = $avant;
		$erreurs = array();
		$texte   = 'Réglages enregistrés.';

		if ( 'connexion' === $onglet ) {
			$adresse = sanitize_title( isset( $_POST['adresse'] ) ? wp_unslash( $_POST['adresse'] ) : '' );
			if ( in_array( $adresse, vf_securite_adresses_interdites(), true ) ) {
				$erreurs[] = 'Cette adresse de connexion n’est pas utilisable (vide, ou réservée par WordPress) : l’ancienne est conservée.';
				$adresse   = $avant['adresse'];
			} elseif ( $adresse !== $avant['adresse'] && get_page_by_path( $adresse, OBJECT, array( 'page', 'post' ) ) ) {
				$erreurs[] = 'Une page du site porte déjà l’adresse « ' . $adresse . ' » : l’ancienne adresse de connexion est conservée.';
				$adresse   = $avant['adresse'];
			}
			$confiance = array();
			foreach ( preg_split( '/[\s,;]+/', isset( $_POST['confiance'] ) ? sanitize_textarea_field( wp_unslash( $_POST['confiance'] ) ) : '' ) as $ip ) {
				if ( '' === $ip ) {
					continue;
				}
				if ( vf_securite_ip_valide( $ip ) ) {
					$confiance[] = $ip;
				} else {
					$erreurs[] = '« ' . $ip . ' » n’est pas une adresse IP valide : elle n’a pas été enregistrée.';
				}
			}
			$r['adresse']    = $adresse;
			$r['masquer']    = vf_securite_case( 'masquer' );
			$r['tentatives'] = max( 1, min( 20, (int) ( $_POST['tentatives'] ?? 3 ) ) );
			$r['duree']      = max( 5, min( 1440, (int) ( $_POST['duree'] ?? 60 ) ) );
			$r['recidive']   = vf_securite_case( 'recidive' );
			$r['confiance']  = array_values( array_unique( $confiance ) );
			$r['source_ip']  = isset( $_POST['source_ip'] ) ? sanitize_key( wp_unslash( $_POST['source_ip'] ) ) : 'directe';
			$r['courriel']   = vf_securite_case( 'courriel' );
			if ( $adresse !== $avant['adresse'] ) {
				$texte .= ' Nouvelle adresse de connexion : ' . home_url( '/' . $adresse ) . ' (à transmettre aux agents).';
			}
		} elseif ( 'comptes' === $onglet ) {
			$r['mdp_regles']      = vf_securite_case( 'mdp_regles' );
			$r['mdp_longueur']    = max( 8, min( 64, (int) ( $_POST['mdp_longueur'] ?? 12 ) ) );
			$r['inactivite']      = vf_securite_case( 'inactivite_active' ) ? max( 15, min( 1440, (int) ( $_POST['inactivite'] ?? 120 ) ) ) : 0;
			$r['alerte_appareil'] = vf_securite_case( 'alerte_appareil' );
		} elseif ( 'site' === $onglet ) {
			$r['entetes']       = vf_securite_case( 'entetes' );
			$r['fichiers']      = vf_securite_case( 'fichiers' );
			$r['api']           = vf_securite_case( 'api' );
			$r['commentaires']  = vf_securite_case( 'commentaires' );
			$r['api_publiques'] = array_values( array_filter( array_map( 'vf_securite_route_propre', preg_split( '/[\s,;]+/', isset( $_POST['api_publiques'] ) ? sanitize_textarea_field( wp_unslash( $_POST['api_publiques'] ) ) : '' ) ) ) );
		} elseif ( 'page' === $onglet ) {
			$r['habillage']   = vf_securite_case( 'habillage' );
			$r['porte_titre'] = isset( $_POST['porte_titre'] ) ? sanitize_text_field( wp_unslash( $_POST['porte_titre'] ) ) : '';
			$r['porte_texte'] = isset( $_POST['porte_texte'] ) ? sanitize_text_field( wp_unslash( $_POST['porte_texte'] ) ) : '';
			$image            = absint( $_POST['porte_image'] ?? 0 );
			$r['porte_image'] = ( $image && wp_attachment_is_image( $image ) ) ? $image : 0;
		} elseif ( 'journal' === $onglet ) {
			$r['conservation'] = max( 30, min( 365, (int) ( $_POST['conservation'] ?? 180 ) ) );
		}
		update_option( 'vf_securite_reglages', $r );
		vf_securite_reglages_oublier();
		if ( 'site' === $onglet ) {
			$etat = vf_securite_htaccess_etat();
			if ( in_array( $etat['etat'], array( 'erreur', 'manuel' ), true ) && ( $r['fichiers'] || $r['entetes'] ) ) {
				$erreurs[] = $etat['message'];
			}
		}
		vf_securite_message( $erreurs ? $texte . ' ' . implode( ' ', $erreurs ) : $texte, $erreurs ? 'error' : 'success' );
	}
	wp_safe_redirect( vf_securite_url_admin( $onglet ) );
	exit;
}

/**
 * Pastille d'état.
 *
 * @param string $etat  oui | non | attention.
 * @param string $texte Texte de la pastille.
 * @return string
 */
function vf_securite_pastille( $etat, $texte ) {
	return '<span class="vf-sec-pastille vf-sec-pastille--' . esc_attr( $etat ) . '">' . esc_html( $texte ) . '</span>';
}

/**
 * Liste des protections et de leur état, pour la vue d'ensemble.
 *
 * @return array[] [ nom, explication, état, texte de l'état, onglet ].
 */
function vf_securite_protections() {
	$r        = vf_securite_reglages();
	$fichiers = vf_securite_htaccess_etat();
	$actif    = static function ( $oui ) {
		return $oui ? array( 'oui', 'Actif' ) : array( 'non', 'Inactif' );
	};
	$liste    = array(
		array( 'Adresse de connexion masquée', 'La connexion se fait à l’adresse /' . $r['adresse'] . ' ; wp-login.php et wp-admin répondent « page introuvable » aux visiteurs.', $actif( $r['masquer'] ), 'connexion' ),
		array( 'Blocage après ' . $r['tentatives'] . ' échecs', 'L’adresse IP est bloquée ' . vf_securite_duree_en_clair( $r['duree'] ) . ( $r['recidive'] ? ', puis 24 heures en cas de récidive' : '' ) . '.', array( 'oui', 'Actif' ), 'connexion' ),
		array( 'Mots de passe solides', $r['mdp_longueur'] . ' caractères au moins, variés, sans mot courant ni identifiant.', $actif( $r['mdp_regles'] ), 'comptes' ),
		array( 'Déconnexion après inactivité', $r['inactivite'] ? 'Après ' . vf_securite_duree_en_clair( $r['inactivite'] ) . ' sans activité.' : 'Les sessions restent ouvertes jusqu’à leur expiration normale.', $actif( $r['inactivite'] ), 'comptes' ),
		array( 'Alerte « nouvel appareil »', 'Le titulaire du compte reçoit un courriel à chaque connexion depuis un appareil inconnu.', $actif( $r['alerte_appareil'] ), 'comptes' ),
		array( 'En-têtes de sécurité', 'HTTPS imposé au navigateur, site non affichable dans le cadre d’un autre site, versions masquées.', $actif( $r['entetes'] ), 'site' ),
		array(
			'Fichiers techniques fermés',
			$fichiers['message'],
			! $r['fichiers'] ? array( 'non', 'Inactif' ) : ( 'actif' === $fichiers['etat'] ? array( 'oui', 'Actif' ) : array( 'attention', 'À vérifier' ) ),
			'site',
		),
		array( 'API de WordPress réservée', 'La liste des pages, des médias et des comptes n’est plus lisible par un visiteur.', $actif( $r['api'] ), 'site' ),
		array( 'Commentaires et rétroliens coupés', 'Première cible du spam ; le site n’en publie pas.', $actif( $r['commentaires'] ), 'site' ),
		array( 'Éditeur de fichiers désactivé', 'Un compte administrateur volé ne peut pas modifier le code du site depuis l’administration.', $actif( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ), '' ),
		array( 'Connexion chiffrée (HTTPS)', is_ssl() ? 'Le site est servi en HTTPS.' : 'Le site n’est pas servi en HTTPS : les mots de passe circulent en clair.', is_ssl() ? array( 'oui', 'Actif' ) : array( 'attention', 'À corriger' ), '' ),
		array( 'Journal d’activité', 'Connexions et actions sensibles conservées ' . $r['conservation'] . ' jours.', array( 'oui', 'Actif' ), 'journal' ),
	);
	return $liste;
}

/**
 * Tableau des adresses bloquées.
 */
function vf_securite_afficher_blocages() {
	$blocages = vf_securite_blocages();
	$mon_ip   = vf_securite_ip();
	?>
	<h2>Adresses bloquées</h2>
	<?php if ( ! $blocages ) : ?>
		<p>Aucune adresse n’est bloquée en ce moment.</p>
	<?php else : ?>
		<table class="widefat striped vf-sec-table">
			<thead><tr><th scope="col">Adresse IP</th><th scope="col">Identifiant essayé</th><th scope="col">Bloquée depuis</th><th scope="col">Jusqu’à</th><th scope="col">Action</th></tr></thead>
			<tbody>
			<?php foreach ( $blocages as $ip => $b ) : ?>
				<tr>
					<td><code><?php echo esc_html( $ip ); ?></code><?php echo $ip === $mon_ip ? ' <em>(votre adresse)</em>' : ''; ?></td>
					<td><?php echo esc_html( $b['identifiant'] ? $b['identifiant'] : '(vide)' ); ?></td>
					<td><?php echo esc_html( wp_date( 'j M à H\hi', (int) $b['debut'] ) ); ?></td>
					<td><?php echo esc_html( wp_date( 'j M à H\hi', (int) $b['fin'] ) ); ?> (encore <?php $vf_min = vf_securite_minutes_restantes( $b ); echo esc_html( $vf_min > 90 ? 'environ ' . vf_securite_duree_en_clair( 60 * (int) ceil( $vf_min / 60 ) ) : vf_securite_duree_en_clair( $vf_min ) ); ?>)</td>
					<td>
						<form method="post" action="<?php echo esc_url( vf_securite_url_admin( 'etat' ) ); ?>">
							<?php wp_nonce_field( 'vf_securite' ); ?>
							<input type="hidden" name="vf_action" value="debloquer">
							<input type="hidden" name="ip" value="<?php echo esc_attr( $ip ); ?>">
							<button type="submit" class="button">Débloquer<span class="screen-reader-text"> l’adresse <?php echo esc_html( $ip ); ?></span></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( count( $blocages ) > 1 ) : ?>
		<form method="post" action="<?php echo esc_url( vf_securite_url_admin( 'etat' ) ); ?>" style="margin-top:10px">
			<?php wp_nonce_field( 'vf_securite' ); ?>
			<input type="hidden" name="vf_action" value="tout-debloquer">
			<button type="submit" class="button">Tout débloquer</button>
		</form>
		<?php endif; ?>
	<?php endif; ?>
	<p class="description">Un blocage empêche seulement de <em>se connecter</em> depuis cette adresse : les personnes déjà connectées continuent de travailler, et le site public reste accessible.</p>
	<?php
}

/**
 * Onglet « Vue d'ensemble ».
 */
function vf_securite_onglet_etat() {
	?>
	<h2>Protections</h2>
	<table class="widefat striped vf-sec-table">
		<thead><tr><th scope="col">Protection</th><th scope="col">Ce qu’elle fait</th><th scope="col">État</th></tr></thead>
		<tbody>
		<?php foreach ( vf_securite_protections() as $p ) : ?>
			<tr>
				<th scope="row">
					<?php if ( $p[3] ) : ?>
						<a href="<?php echo esc_url( vf_securite_url_admin( $p[3] ) ); ?>"><?php echo esc_html( $p[0] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $p[0] ); ?>
					<?php endif; ?>
				</th>
				<td><?php echo esc_html( $p[1] ); ?></td>
				<td><?php echo vf_securite_pastille( $p[2][0], $p[2][1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans la fonction. ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php vf_securite_afficher_blocages(); ?>

	<h2>À vérifier chez l’hébergeur</h2>
	<p>Quatre protections ne dépendent pas de cette extension, mais du compte d’hébergement :</p>
	<ul class="ul-disc">
		<li><strong>Sauvegardes quotidiennes</strong> du site et de la base, conservées hors du serveur : c’est le seul vrai recours après un incident.</li>
		<li><strong>Mises à jour automatiques</strong> de WordPress, des extensions et de PHP.</li>
		<li><strong>Envoi des courriels (SMTP)</strong> : sans lui, les alertes de sécurité et les liens « mot de passe oublié » n’arrivent pas.</li>
		<li><strong>Accès au compte d’hébergement</strong> : mot de passe unique et validation en deux étapes pour toute personne qui peut ouvrir le panneau de l’hébergeur.</li>
	</ul>

	<h2>En cas de problème d’accès</h2>
	<p>Si plus personne ne peut se connecter, ajoutez cette ligne dans le fichier <code>wp-config.php</code> (gestionnaire de fichiers de l’hébergeur), le temps de régler le problème :</p>
	<p><code>define( 'VF_SECURITE_PAUSE', true );</code></p>
	<p>Toutes les protections sont alors suspendues et l’adresse d’origine <code>wp-login.php</code> fonctionne de nouveau. Retirez la ligne ensuite.</p>
	<?php
}

/**
 * Onglet « Connexion ».
 *
 * @param array $r Réglages.
 */
function vf_securite_onglet_connexion( $r ) {
	$mon_ip = vf_securite_ip();
	?>
	<h2>Adresse de connexion</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="vf-adresse">Page de connexion</label></th>
			<td>
				<code><?php echo esc_html( home_url( '/' ) ); ?></code><input type="text" id="vf-adresse" name="adresse" value="<?php echo esc_attr( $r['adresse'] ); ?>" class="regular-text" style="max-width:220px" aria-describedby="vf-adresse-aide">
				<p class="description" id="vf-adresse-aide">Lettres, chiffres et tirets. C’est l’adresse à donner aux agents pour se connecter. Une fois connecté, le tableau de bord reste à l’adresse /wp-admin/.</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Anciennes adresses</th>
			<td>
				<label><input type="checkbox" name="masquer" value="1" <?php checked( $r['masquer'] ); ?>> Masquer <code>wp-login.php</code> et <code>wp-admin</code> aux visiteurs non connectés</label>
				<p class="description">Ils obtiennent « page introuvable ». Si votre session a expiré, revenez par la page de connexion ci-dessus.</p>
			</td>
		</tr>
	</table>

	<h2>Blocage après plusieurs échecs</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="vf-tentatives">Nombre d’essais</label></th>
			<td><input type="number" id="vf-tentatives" name="tentatives" min="1" max="20" value="<?php echo (int) $r['tentatives']; ?>" class="small-text"> échecs de connexion avant le blocage de l’adresse IP</td>
		</tr>
		<tr>
			<th scope="row"><label for="vf-duree">Durée du blocage</label></th>
			<td><input type="number" id="vf-duree" name="duree" min="5" max="1440" value="<?php echo (int) $r['duree']; ?>" class="small-text"> minutes</td>
		</tr>
		<tr>
			<th scope="row">Récidive</th>
			<td>
				<label><input type="checkbox" name="recidive" value="1" <?php checked( $r['recidive'] ); ?>> Bloquer 24 heures une adresse bloquée une deuxième fois en moins de 24 heures</label>
				<p class="description">Un robot qui revient à la charge est écarté pour la journée. Si vous débloquez vous-même une adresse (un agent qui s’est trompé), elle repart de zéro.</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="vf-confiance">Adresses IP de confiance</label></th>
			<td>
				<textarea id="vf-confiance" name="confiance" rows="3" class="regular-text code" aria-describedby="vf-confiance-aide"><?php echo esc_textarea( implode( "\n", $r['confiance'] ) ); ?></textarea>
				<p class="description" id="vf-confiance-aide">Une par ligne. Ces adresses ne sont jamais bloquées. Conseillé pour l’adresse de la mairie : tous les agents y partagent en général la même adresse IP, et le blocage de l’un bloquerait tous les autres. Votre adresse actuelle : <code><?php echo esc_html( $mon_ip ? $mon_ip : 'inconnue' ); ?></code></p>
			</td>
		</tr>
		<tr>
			<th scope="row">Courriel d’alerte</th>
			<td>
				<label><input type="checkbox" name="courriel" value="1" <?php checked( $r['courriel'] ); ?>> Prévenir <code><?php echo esc_html( get_option( 'admin_email' ) ); ?></code> à chaque blocage, avec un lien pour débloquer</label>
				<p class="description">Cinq courriels par heure au plus. Le lien permet de débloquer même sans pouvoir se connecter.</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="vf-source">Lecture de l’adresse IP</label></th>
			<td>
				<select id="vf-source" name="source_ip" aria-describedby="vf-source-aide">
					<?php foreach ( vf_securite_sources_ip() as $cle => $source ) : ?>
						<?php $vue = vf_securite_ip_depuis( $cle ); ?>
						<option value="<?php echo esc_attr( $cle ); ?>" <?php selected( $r['source_ip'], $cle ); ?>><?php echo esc_html( $source[0] . ' : ' . ( $vue ? $vue : 'absent' ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description" id="vf-source-aide">À ne changer que si le site passe par un CDN (toutes les visites arrivent alors avec l’adresse du CDN). Sans CDN, gardez « Connexion directe » : un en-tête peut être falsifié par un attaquant pour échapper au blocage.</p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Onglet « Comptes ».
 *
 * @param array $r Réglages.
 */
function vf_securite_onglet_comptes( $r ) {
	?>
	<h2>Mots de passe</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">Mots de passe solides</th>
			<td>
				<label><input type="checkbox" name="mdp_regles" value="1" <?php checked( $r['mdp_regles'] ); ?>> Refuser les mots de passe trop faibles</label>
				<p class="description">La règle s’applique à chaque création de compte et à chaque changement de mot de passe : longueur minimale, au moins trois types de caractères (ou une phrase de 20 caractères et plus), pas d’identifiant, pas de mot courant comme « azerty » ou « villefontaine ». Les mots de passe déjà en place ne sont pas modifiés.</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="vf-longueur">Longueur minimale</label></th>
			<td><input type="number" id="vf-longueur" name="mdp_longueur" min="8" max="64" value="<?php echo (int) $r['mdp_longueur']; ?>" class="small-text"> caractères <span class="description">(12 est la valeur recommandée par la CNIL)</span></td>
		</tr>
	</table>

	<h2>Sessions</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">Inactivité</th>
			<td>
				<label><input type="checkbox" name="inactivite_active" value="1" <?php checked( $r['inactivite'] > 0 ); ?>> Déconnecter automatiquement après</label>
				<label for="vf-inactivite" class="screen-reader-text">Minutes sans activité avant la déconnexion</label>
				<input type="number" id="vf-inactivite" name="inactivite" min="15" max="1440" value="<?php echo (int) ( $r['inactivite'] ? $r['inactivite'] : 120 ); ?>" class="small-text"> minutes sans activité
				<p class="description">Protège un poste laissé ouvert. Un texte en cours de rédaction n’est pas perdu : l’éditeur propose de se reconnecter sans quitter la page.</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Nouvel appareil</th>
			<td>
				<label><input type="checkbox" name="alerte_appareil" value="1" <?php checked( $r['alerte_appareil'] ); ?>> Prévenir le titulaire du compte par courriel à chaque connexion depuis un appareil ou un navigateur inconnu</label>
				<p class="description">Si quelqu’un d’autre utilise son mot de passe, l’agent le sait tout de suite. L’appareil est reconnu grâce à un témoin (cookie) technique déposé à la connexion, sans aucun suivi de la navigation.</p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Onglet « Protection du site ».
 *
 * @param array $r Réglages.
 */
function vf_securite_onglet_site( $r ) {
	$etat   = vf_securite_htaccess_etat();
	$regles = vf_securite_htaccess_regles();
	?>
	<h2>Ce que le site montre et laisse atteindre</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">En-têtes de sécurité</th>
			<td>
				<label><input type="checkbox" name="entetes" value="1" <?php checked( $r['entetes'] ); ?>> Envoyer les en-têtes de sécurité avec chaque page</label>
				<p class="description">Le navigateur n’accepte plus le site qu’en HTTPS, refuse de l’afficher dans le cadre d’un autre site (piège classique pour voler un mot de passe), et ne reçoit plus la version de PHP.</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Fichiers techniques</th>
			<td>
				<label><input type="checkbox" name="fichiers" value="1" <?php checked( $r['fichiers'] ); ?>> Fermer les fichiers d’installation, les anciens accès à distance et l’appel direct des scripts</label>
				<p class="description">readme.html, install.php, xmlrpc.php, scripts des extensions… répondent « page introuvable ». Aucun script ne peut être exécuté depuis le dossier des médias.</p>
				<p><?php echo vf_securite_pastille( 'actif' === $etat['etat'] ? 'oui' : ( 'retire' === $etat['etat'] || 'inconnu' === $etat['etat'] ? 'non' : 'attention' ), 'actif' === $etat['etat'] ? 'Règles en place' : ( 'retire' === $etat['etat'] ? 'Règles retirées' : ( 'inconnu' === $etat['etat'] ? 'Pas encore écrites' : 'À vérifier' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo esc_html( $etat['message'] ); ?><?php echo $etat['quand'] ? ' <span class="description">(' . esc_html( wp_date( 'j M Y à H\hi', (int) $etat['quand'] ) ) . ')</span>' : ''; ?></p>
				<?php if ( $regles && in_array( $etat['etat'], array( 'manuel', 'erreur' ), true ) ) : ?>
					<details>
						<summary>Règles à copier en tête du fichier .htaccess</summary>
						<textarea readonly rows="12" class="large-text code" aria-label="Règles à copier dans le fichier .htaccess"><?php echo esc_textarea( VF_SECURITE_BALISE_DEBUT . "\n" . implode( "\n", $regles ) . "\n" . VF_SECURITE_BALISE_FIN ); ?></textarea>
					</details>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row">API de WordPress</th>
			<td>
				<label><input type="checkbox" name="api" value="1" <?php checked( $r['api'] ); ?>> Réserver l’API aux personnes connectées</label>
				<p class="description">Sans cette protection, n’importe qui peut lister les pages, les médias et les réglages publics du site à l’adresse /wp-json/. La recherche et les formulaires du site (routes « vf-… ») restent ouverts au public.</p>
				<p><label for="vf-api-publiques">Autres routes à laisser publiques (une par ligne, par exemple <code>contact-form-7/v1</code>) :</label><br>
				<textarea id="vf-api-publiques" name="api_publiques" rows="2" class="regular-text code"><?php echo esc_textarea( implode( "\n", $r['api_publiques'] ) ); ?></textarea></p>
			</td>
		</tr>
		<tr>
			<th scope="row">Commentaires</th>
			<td>
				<label><input type="checkbox" name="commentaires" value="1" <?php checked( $r['commentaires'] ); ?>> Désactiver les commentaires et les rétroliens sur tout le site</label>
				<p class="description">Le site n’en publie pas ; les laisser ouverts ne sert qu’aux robots de spam.</p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Onglet « Page de connexion ».
 *
 * @param array $r Réglages.
 */
function vf_securite_onglet_page( $r ) {
	$apercu = $r['porte_image'] ? wp_get_attachment_image_url( $r['porte_image'], 'medium' ) : '';
	$defaut = vf_securite_fichier_url( 'img/lac-768.webp' );
	?>
	<h2>Apparence de la page de connexion</h2>
	<p><a class="button" href="<?php echo esc_url( vf_securite_url_connexion() ); ?>" target="_blank" rel="noopener">Voir la page de connexion<span class="screen-reader-text"> (nouvel onglet)</span></a></p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">Habillage</th>
			<td>
				<label><input type="checkbox" name="habillage" value="1" <?php checked( $r['habillage'] ); ?>> Afficher la page de connexion aux couleurs de la Ville</label>
				<p class="description">Décoché, la page reprend l’apparence d’origine de WordPress.</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="vf-porte-titre">Titre du volet</label></th>
			<td><input type="text" id="vf-porte-titre" name="porte_titre" value="<?php echo esc_attr( $r['porte_titre'] ); ?>" class="regular-text" maxlength="60"></td>
		</tr>
		<tr>
			<th scope="row"><label for="vf-porte-texte">Phrase d’accueil</label></th>
			<td><input type="text" id="vf-porte-texte" name="porte_texte" value="<?php echo esc_attr( $r['porte_texte'] ); ?>" class="large-text" maxlength="200"></td>
		</tr>
		<tr>
			<th scope="row">Photo</th>
			<td>
				<input type="hidden" id="vf-porte-image" name="porte_image" value="<?php echo (int) $r['porte_image']; ?>">
				<p><img id="vf-porte-apercu" src="<?php echo esc_url( $apercu ? $apercu : $defaut ); ?>" data-defaut="<?php echo esc_url( $defaut ); ?>" alt="Photo actuelle du volet" style="display:block;max-width:300px;height:auto;border-radius:6px"></p>
				<p>
					<button type="button" class="button" id="vf-porte-choisir">Choisir une photo dans la médiathèque</button>
					<button type="button" class="button-link" id="vf-porte-retirer" <?php echo $r['porte_image'] ? '' : 'hidden'; ?>>Revenir à la photo du lac</button>
				</p>
				<p class="description">Format paysage ou carré, 1 200 pixels de large au moins. La photo est assombrie par un voile bleu pour que le texte reste lisible.</p>
			</td>
		</tr>
	</table>
	<script>
	( function () {
		var choisir = document.getElementById( 'vf-porte-choisir' ), retirer = document.getElementById( 'vf-porte-retirer' ),
			champ = document.getElementById( 'vf-porte-image' ), apercu = document.getElementById( 'vf-porte-apercu' ), cadre;
		if ( ! choisir ) { return; }
		choisir.addEventListener( 'click', function () {
			// La médiathèque est chargée en fin de page : on la cherche au moment du clic, pas avant.
			if ( ! window.wp || ! wp.media ) { return; }
			if ( ! cadre ) {
				cadre = wp.media( { title: 'Photo de la page de connexion', library: { type: 'image' }, button: { text: 'Utiliser cette photo' }, multiple: false } );
				cadre.on( 'select', function () {
					var image = cadre.state().get( 'selection' ).first().toJSON();
					champ.value = image.id;
					apercu.src = ( image.sizes && image.sizes.medium ? image.sizes.medium.url : image.url );
					retirer.hidden = false;
				} );
			}
			cadre.open();
		} );
		retirer.addEventListener( 'click', function () {
			champ.value = 0;
			apercu.src = apercu.getAttribute( 'data-defaut' );
			retirer.hidden = true;
			choisir.focus();
		} );
	}() );
	</script>
	<?php
}

/**
 * Onglet « Journal ».
 *
 * @param array $r Réglages.
 */
function vf_securite_onglet_journal( $r ) {
	// phpcs:disable WordPress.Security.NonceVerification -- simple consultation.
	$famille = isset( $_GET['famille'] ) ? sanitize_key( wp_unslash( $_GET['famille'] ) ) : '';
	$famille = isset( vf_securite_familles()[ $famille ] ) ? $famille : '';
	$page    = max( 1, absint( $_GET['pg'] ?? 1 ) );
	// phpcs:enable
	$par                    = 50;
	list( $lignes, $total ) = vf_securite_journal_lire( $famille, $page, $par );
	$types                  = vf_securite_types_evenements();
	$pages                  = max( 1, (int) ceil( $total / $par ) );
	?>
	<h2>Journal de sécurité et d’activité</h2>
	<ul class="subsubsub" style="float:none;margin:0 0 10px">
		<?php $n = 0; ?>
		<?php foreach ( vf_securite_familles() as $cle => $nom ) : ?>
			<li><?php echo $n++ ? ' | ' : ''; ?><a href="<?php echo esc_url( vf_securite_url_admin( 'journal', $cle ? array( 'famille' => $cle ) : array() ) ); ?>" <?php echo $cle === $famille ? 'class="current" aria-current="page"' : ''; ?>><?php echo esc_html( $nom ); ?></a></li>
		<?php endforeach; ?>
	</ul>
	<?php if ( ! $lignes ) : ?>
		<p>Aucun événement.</p>
	<?php else : ?>
		<table class="widefat striped vf-sec-table vf-sec-table--large">
			<thead><tr><th scope="col">Date</th><th scope="col">Événement</th><th scope="col">Compte</th><th scope="col">Adresse IP</th><th scope="col">Précision</th></tr></thead>
			<tbody>
			<?php foreach ( $lignes as $l ) : ?>
				<tr>
					<td><?php echo esc_html( wp_date( 'j M Y à H\hi', (int) $l['quand'] ) ); ?></td>
					<td><?php echo esc_html( $types[ $l['type'] ][0] ?? $l['type'] ); ?></td>
					<td><?php echo esc_html( $l['compte'] ); ?></td>
					<td><?php echo $l['ip'] ? '<code>' . esc_html( $l['ip'] ) . '</code>' : ''; ?></td>
					<td><?php echo esc_html( $l['detail'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="tablenav-pages" style="margin-top:10px">
			<?php echo esc_html( number_format_i18n( $total ) ); ?> événement<?php echo $total > 1 ? 's' : ''; ?>, page <?php echo (int) $page; ?> sur <?php echo (int) $pages; ?>
			<?php if ( $page > 1 ) : ?>
				&nbsp;<a class="button" href="<?php echo esc_url( vf_securite_url_admin( 'journal', array_filter( array( 'famille' => $famille, 'pg' => $page - 1 ) ) ) ); ?>">Page précédente</a>
			<?php endif; ?>
			<?php if ( $page < $pages ) : ?>
				&nbsp;<a class="button" href="<?php echo esc_url( vf_securite_url_admin( 'journal', array_filter( array( 'famille' => $famille, 'pg' => $page + 1 ) ) ) ); ?>">Page suivante</a>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<h2>Conservation</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="vf-conservation">Durée de conservation</label></th>
			<td>
				<input type="number" id="vf-conservation" name="conservation" min="30" max="365" value="<?php echo (int) $r['conservation']; ?>" class="small-text"> jours
				<p class="description">Les lignes plus anciennes sont supprimées chaque jour. Le journal contient des adresses IP et des identifiants : c’est un traitement de données personnelles à inscrire au registre de la collectivité (finalité : sécurité du site ; la CNIL recommande de conserver ce type de journal entre six mois et un an).</p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Affiche la page.
 */
function vf_securite_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$r       = vf_securite_reglages();
	$onglet  = vf_securite_onglet_courant();
	$message = get_transient( 'vf_securite_message_' . get_current_user_id() );
	?>
	<div class="wrap vf-sec">
		<style>
			.vf-sec-table { max-width: 1000px; }
			.vf-sec-table--large { max-width: 1200px; }
			.vf-sec-table th[scope="row"] { font-weight: 600; width: 26%; }
			.vf-sec-pastille { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; line-height: 1.7; white-space: nowrap; }
			.vf-sec-pastille--oui { background: #e3f1dc; color: #1e4d0f; }
			.vf-sec-pastille--non { background: #ececec; color: #3c434a; }
			.vf-sec-pastille--attention { background: #fcefc7; color: #5c3d00; }
		</style>
		<h1>Sécurité</h1>
		<?php if ( $message ) : ?>
			<?php delete_transient( 'vf_securite_message_' . get_current_user_id() ); ?>
			<div class="notice notice-<?php echo 'error' === $message[1] ? 'error' : 'success'; ?>"><p><?php echo esc_html( $message[0] ); ?></p></div>
		<?php endif; ?>
		<?php if ( vf_securite_en_pause() ) : ?>
			<div class="notice notice-warning"><p><strong>Protection en pause.</strong> La ligne <code>VF_SECURITE_PAUSE</code> est présente dans wp-config.php : toutes les protections sont suspendues. Retirez cette ligne pour les réactiver.</p></div>
		<?php endif; ?>

		<nav class="nav-tab-wrapper" aria-label="Rubriques de la sécurité">
			<?php foreach ( vf_securite_onglets() as $cle => $nom ) : ?>
				<a class="nav-tab<?php echo $cle === $onglet ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( vf_securite_url_admin( $cle ) ); ?>" <?php echo $cle === $onglet ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $nom ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( 'etat' === $onglet ) : ?>
			<?php vf_securite_onglet_etat(); ?>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( vf_securite_url_admin( $onglet ) ); ?>">
				<?php wp_nonce_field( 'vf_securite' ); ?>
				<input type="hidden" name="vf_action" value="reglages">
				<?php call_user_func( 'vf_securite_onglet_' . $onglet, $r ); ?>
				<?php submit_button( 'Enregistrer les réglages' ); ?>
			</form>
			<?php if ( 'site' === $onglet && $r['fichiers'] ) : ?>
				<form method="post" action="<?php echo esc_url( vf_securite_url_admin( 'site' ) ); ?>">
					<?php wp_nonce_field( 'vf_securite' ); ?>
					<input type="hidden" name="vf_action" value="fichiers">
					<p><button type="submit" class="button">Réécrire et vérifier les règles de fichiers</button> <span class="description">À utiliser après un changement d’hébergement ou si le fichier .htaccess a été remplacé.</span></p>
				</form>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}
