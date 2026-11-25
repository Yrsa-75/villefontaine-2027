<?php
/**
 * Page d'administration : Réglages > Sécurité de la connexion.
 * Réservée aux administrateurs ; chaque action est protégée par un jeton anti-falsification.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ajoute la page au menu Réglages.
 */
function vf_securite_menu() {
	$page = add_options_page( 'Sécurité de la connexion', 'Sécurité de la connexion', 'manage_options', 'vf-securite', 'vf_securite_page' );
	add_action( 'load-' . $page, 'vf_securite_traiter' );
}
add_action( 'admin_menu', 'vf_securite_menu' );

/**
 * Lien « Réglages » dans la liste des extensions.
 *
 * @param string[] $liens Liens d'action.
 * @return string[]
 */
function vf_securite_lien_reglages( $liens ) {
	array_unshift( $liens, '<a href="' . esc_url( admin_url( 'options-general.php?page=vf-securite' ) ) . '">Réglages</a>' );
	return $liens;
}
add_filter( 'plugin_action_links_securite-villefontaine/securite-villefontaine.php', 'vf_securite_lien_reglages' );

/**
 * Après l'activation : rappel de la nouvelle adresse de connexion.
 */
function vf_securite_bienvenue() {
	if ( ! current_user_can( 'manage_options' ) || ! get_transient( 'vf_securite_bienvenue' ) ) {
		return;
	}
	delete_transient( 'vf_securite_bienvenue' );
	echo '<div class="notice notice-warning"><p><strong>Sécurité Villefontaine est active.</strong> La page de connexion est maintenant à l’adresse <code>' . esc_html( vf_securite_url_connexion() ) . '</code> : notez-la et transmettez-la aux agents. Les adresses wp-login.php et wp-admin ne répondent plus aux visiteurs non connectés. <a href="' . esc_url( admin_url( 'options-general.php?page=vf-securite' ) ) . '">Voir les réglages</a></p></div>';
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
 * Traite les formulaires de la page.
 */
function vf_securite_traiter() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'vf_securite' );
	$action = isset( $_POST['vf_action'] ) ? sanitize_key( wp_unslash( $_POST['vf_action'] ) ) : '';

	if ( 'debloquer' === $action ) {
		$ip = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
		vf_securite_message( vf_securite_debloquer( $ip, 'administrateur ' . wp_get_current_user()->user_login ) ? 'L’adresse ' . $ip . ' est débloquée.' : 'Cette adresse n’était plus bloquée.' );
	} elseif ( 'tout-debloquer' === $action ) {
		foreach ( array_keys( vf_securite_blocages() ) as $ip ) {
			vf_securite_debloquer( $ip, 'administrateur ' . wp_get_current_user()->user_login );
		}
		vf_securite_message( 'Toutes les adresses sont débloquées.' );
	} elseif ( 'reglages' === $action ) {
		$avant   = vf_securite_reglages();
		$erreurs = array();
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
		update_option(
			'vf_securite_reglages',
			array(
				'adresse'    => $adresse,
				'masquer'    => empty( $_POST['masquer'] ) ? 0 : 1,
				'tentatives' => max( 1, min( 20, (int) ( $_POST['tentatives'] ?? 3 ) ) ),
				'duree'      => max( 5, min( 1440, (int) ( $_POST['duree'] ?? 60 ) ) ),
				'confiance'  => array_values( array_unique( $confiance ) ),
				'source_ip'  => isset( $_POST['source_ip'] ) ? sanitize_key( wp_unslash( $_POST['source_ip'] ) ) : 'directe',
				'courriel'   => empty( $_POST['courriel'] ) ? 0 : 1,
			)
		);
		$texte = 'Réglages enregistrés.';
		if ( $adresse !== $avant['adresse'] ) {
			$texte .= ' Nouvelle adresse de connexion : ' . home_url( '/' . $adresse ) . ' (à transmettre aux agents).';
		}
		vf_securite_message( $erreurs ? $texte . ' ' . implode( ' ', $erreurs ) : $texte, $erreurs ? 'error' : 'success' );
	}
	wp_safe_redirect( admin_url( 'options-general.php?page=vf-securite' ) );
	exit;
}

/**
 * Affiche la page.
 */
function vf_securite_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$r        = vf_securite_reglages();
	$blocages = vf_securite_blocages();
	$journal  = array_reverse( array_slice( (array) get_option( 'vf_securite_journal', array() ), -50 ) );
	$mon_ip   = vf_securite_ip();
	$message  = get_transient( 'vf_securite_message_' . get_current_user_id() );
	$types    = array(
		'echec'     => 'Échec de connexion',
		'blocage'   => 'Adresse bloquée',
		'refus'     => 'Tentative pendant un blocage',
		'deblocage' => 'Adresse débloquée',
	);
	?>
	<div class="wrap">
		<h1>Sécurité de la connexion</h1>
		<?php if ( $message ) : ?>
			<?php delete_transient( 'vf_securite_message_' . get_current_user_id() ); ?>
			<div class="notice notice-<?php echo 'error' === $message[1] ? 'error' : 'success'; ?>"><p><?php echo esc_html( $message[0] ); ?></p></div>
		<?php endif; ?>
		<?php if ( vf_securite_en_pause() ) : ?>
			<div class="notice notice-warning"><p><strong>Protection en pause.</strong> La ligne <code>VF_SECURITE_PAUSE</code> est présente dans wp-config.php : le masquage et les blocages sont suspendus. Retirez cette ligne pour les réactiver.</p></div>
		<?php endif; ?>

		<h2>Adresses bloquées</h2>
		<?php if ( ! $blocages ) : ?>
			<p>Aucune adresse n’est bloquée en ce moment.</p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:900px">
				<thead><tr><th scope="col">Adresse IP</th><th scope="col">Identifiant essayé</th><th scope="col">Bloquée depuis</th><th scope="col">Jusqu’à</th><th scope="col">Action</th></tr></thead>
				<tbody>
				<?php foreach ( $blocages as $ip => $b ) : ?>
					<tr>
						<td><code><?php echo esc_html( $ip ); ?></code><?php echo $ip === $mon_ip ? ' <em>(votre adresse)</em>' : ''; ?></td>
						<td><?php echo esc_html( $b['identifiant'] ? $b['identifiant'] : '(vide)' ); ?></td>
						<td><?php echo esc_html( wp_date( 'j M à H\hi', (int) $b['debut'] ) ); ?></td>
						<td><?php echo esc_html( wp_date( 'H\hi', (int) $b['fin'] ) ); ?> (encore <?php echo (int) vf_securite_minutes_restantes( $b ); ?> min)</td>
						<td>
							<form method="post">
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
			<form method="post" style="margin-top:10px">
				<?php wp_nonce_field( 'vf_securite' ); ?>
				<input type="hidden" name="vf_action" value="tout-debloquer">
				<button type="submit" class="button">Tout débloquer</button>
			</form>
			<?php endif; ?>
		<?php endif; ?>
		<p class="description">Un blocage empêche seulement de <em>se connecter</em> depuis cette adresse : les personnes déjà connectées continuent de travailler, et le site public reste accessible.</p>

		<form method="post">
			<?php wp_nonce_field( 'vf_securite' ); ?>
			<input type="hidden" name="vf_action" value="reglages">

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
			<?php submit_button( 'Enregistrer les réglages' ); ?>
		</form>

		<h2>Journal (50 derniers événements, conservés 30 jours)</h2>
		<?php if ( ! $journal ) : ?>
			<p>Aucun événement.</p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:900px">
				<thead><tr><th scope="col">Date</th><th scope="col">Événement</th><th scope="col">Adresse IP</th><th scope="col">Identifiant ou précision</th></tr></thead>
				<tbody>
				<?php foreach ( $journal as $l ) : ?>
					<tr><td><?php echo esc_html( wp_date( 'j M à H\hi', (int) $l[0] ) ); ?></td><td><?php echo esc_html( $types[ $l[1] ] ?? $l[1] ); ?></td><td><code><?php echo esc_html( $l[2] ); ?></code></td><td><?php echo esc_html( $l[3] ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2>En cas de problème d’accès</h2>
		<p>Si plus personne ne peut se connecter, ajoutez cette ligne dans le fichier <code>wp-config.php</code> (gestionnaire de fichiers de l’hébergeur), le temps de régler le problème :</p>
		<p><code>define( 'VF_SECURITE_PAUSE', true );</code></p>
		<p>Le masquage et les blocages sont alors suspendus et l’adresse d’origine <code>wp-login.php</code> fonctionne de nouveau.</p>
	</div>
	<?php
}
