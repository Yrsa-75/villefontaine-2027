<?php
/**
 * Page d'administration « Apparence > Villefontaine » : état des menus et création des menus de départ.
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ajoute la page sous le menu Apparence (réservée à qui peut gérer l'apparence du site).
 */
function vf_socle_menu_admin() {
	add_theme_page(
		'Réglages Villefontaine',
		'Villefontaine',
		'edit_theme_options',
		'vf-socle',
		'vf_socle_page_admin'
	);
}
add_action( 'admin_menu', 'vf_socle_menu_admin' );

/**
 * Traite la création d'un menu de départ (formulaire envoyé vers admin-post.php).
 */
function vf_socle_action_menu_depart() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Vous n’avez pas les droits nécessaires.', 'socle-villefontaine' ), 403 );
	}
	check_admin_referer( 'vf_socle_menu_depart' );

	$emplacement = isset( $_POST['emplacement'] ) ? sanitize_key( wp_unslash( $_POST['emplacement'] ) ) : '';
	if ( ! in_array( $emplacement, array( 'vf-principal', 'vf-demarches' ), true ) ) {
		wp_die( esc_html__( 'Demande invalide.', 'socle-villefontaine' ), 400 );
	}

	$resultat = vf_socle_cree_menu_depart( $emplacement );
	$message  = is_wp_error( $resultat ) ? $resultat->get_error_message() : $resultat;
	set_transient( 'vf_socle_message_' . get_current_user_id(), array( is_wp_error( $resultat ) ? 'warning' : 'success', $message ), 60 );

	wp_safe_redirect( admin_url( 'themes.php?page=vf-socle' ) );
	exit;
}
add_action( 'admin_post_vf_socle_menu_depart', 'vf_socle_action_menu_depart' );

/**
 * Affiche la page.
 */
function vf_socle_page_admin() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$message = get_transient( 'vf_socle_message_' . get_current_user_id() );
	delete_transient( 'vf_socle_message_' . get_current_user_id() );
	$emplacements = get_nav_menu_locations();
	$lignes       = array(
		'vf-principal' => array( 'Méga-menu principal', 'Les 4 grandes rubriques en haut du site, avec leurs sous-rubriques (2 niveaux en dessous).' ),
		'vf-demarches' => array( 'Panneau « Vos démarches »', 'Le panneau qui s’ouvre depuis le bouton vert : profils (Habitant, Professionnel…), rubriques, puis démarches.' ),
	);
	?>
	<div class="wrap">
		<h1>Réglages Villefontaine</h1>
		<?php if ( is_array( $message ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $message[0] ); ?> is-dismissible"><p><?php echo esc_html( $message[1] ); ?></p></div>
		<?php endif; ?>

		<h2>Menus du site</h2>
		<p>Les menus se modifient dans <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Apparence &gt; Menus</a> : glissez-déposez les éléments pour changer l’ordre ou le niveau.</p>
		<table class="widefat striped" style="max-width:960px">
			<thead><tr><th scope="col">Emplacement</th><th scope="col">Rôle</th><th scope="col">Menu affecté</th><th scope="col">Action</th></tr></thead>
			<tbody>
			<?php
			foreach ( $lignes as $cle => $ligne ) :
				$menu = ! empty( $emplacements[ $cle ] ) ? wp_get_nav_menu_object( $emplacements[ $cle ] ) : false;
				?>
				<tr>
					<th scope="row"><strong><?php echo esc_html( $ligne[0] ); ?></strong></th>
					<td><?php echo esc_html( $ligne[1] ); ?></td>
					<td><?php echo $menu ? esc_html( $menu->name ) : '<em>Aucun</em>'; ?></td>
					<td>
						<?php if ( $menu ) : ?>
							<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php?action=edit&menu=' . (int) $menu->term_id ) ); ?>">Modifier le menu</a>
						<?php else : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'vf_socle_menu_depart' ); ?>
								<input type="hidden" name="action" value="vf_socle_menu_depart">
								<input type="hidden" name="emplacement" value="<?php echo esc_attr( $cle ); ?>">
								<button type="submit" class="button button-primary">Créer le menu de départ</button>
							</form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2 style="margin-top:32px">Aide</h2>
		<ul style="list-style:disc;padding-left:20px;max-width:900px">
			<li><strong>Méga-menu :</strong> le texte « Description » d’une grande rubrique s’affiche dans le menu ouvert (activez « Description » dans les « Options de l’écran » de la page Menus). L’image vient de l’image mise en avant de la page liée.</li>
			<li><strong>Vos démarches :</strong> 1<sup>er</sup> niveau = profil, 2<sup>e</sup> niveau = rubrique (ou directement une démarche), 3<sup>e</sup> niveau = démarche. Les liens vers les services en ligne affichent automatiquement le badge « En ligne » et s’ouvrent dans un nouvel onglet.</li>
			<li><strong>Icône d’un profil :</strong> dans « Classes CSS », indiquez par exemple <code>vf-icone-maison</code>, <code>vf-icone-idee</code>, <code>vf-icone-asso</code> ou <code>vf-icone-citoyen</code>.</li>
		</ul>
	</div>
	<?php
}
