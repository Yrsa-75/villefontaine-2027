<?php
/**
 * Page de connexion aux couleurs de la Ville.
 *
 * Écran partagé : à gauche, la photo du lac avec le logo et un message d'accueil ; à droite, le formulaire.
 * Sur téléphone, la photo devient un bandeau au-dessus du formulaire.
 *
 * La page reste celle de WordPress (mêmes champs, mêmes messages, mêmes scripts) : seule l'apparence change,
 * par une feuille de style chargée après celle de WordPress. Polices et images sont fournies avec l'extension,
 * rien n'est chargé depuis un autre site.
 *
 * Accessibilité : le titre de la page (« Se connecter », « Mot de passe oublié »…), que WordPress réserve
 * aux lecteurs d'écran, est rendu visible et reste le titre principal ; contrastes conformes, focus visible,
 * photo décorative ignorée des lecteurs d'écran.
 *
 * @package SecuriteVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * L'habillage est-il actif ?
 *
 * @return bool
 */
function vf_securite_habillage_actif() {
	return ! vf_securite_en_pause() && vf_securite_reglages()['habillage'];
}

/**
 * Adresse d'un fichier fourni avec l'extension.
 *
 * @param string $chemin Chemin relatif au dossier assets.
 * @return string
 */
function vf_securite_fichier_url( $chemin ) {
	return plugins_url( 'assets/' . ltrim( $chemin, '/' ), VF_SECURITE_FICHIER );
}

/**
 * Feuille de style de la page de connexion.
 */
function vf_securite_habillage_styles() {
	if ( vf_securite_habillage_actif() ) {
		wp_enqueue_style( 'vf-securite-porte', vf_securite_fichier_url( 'connexion.css' ), array( 'login' ), VF_SECURITE_VERSION );
	}
}
add_action( 'login_enqueue_scripts', 'vf_securite_habillage_styles', 20 );

/**
 * Les deux polices sont demandées dès le début du chargement : pas de texte qui change d'allure en cours d'affichage.
 */
function vf_securite_habillage_polices() {
	if ( ! vf_securite_habillage_actif() ) {
		return;
	}
	foreach ( array( 'fonts/figtree-latin-wght-normal.woff2', 'fonts/unbounded-latin-wght-normal.woff2' ) as $police ) {
		echo '<link rel="preload" as="font" type="font/woff2" crossorigin href="' . esc_url( vf_securite_fichier_url( $police ) ) . '">' . "\n";
	}
	echo '<meta name="theme-color" content="#0F2F37">' . "\n";
}
add_action( 'login_head', 'vf_securite_habillage_polices', 1 );

/**
 * Classe ajoutée à la page : toutes les règles de la feuille de style en dépendent.
 *
 * @param string[] $classes Classes de la balise body.
 * @return string[]
 */
function vf_securite_habillage_classe( $classes ) {
	if ( vf_securite_habillage_actif() ) {
		$classes[] = 'vf-porte-active';
	}
	return $classes;
}
add_filter( 'login_body_class', 'vf_securite_habillage_classe' );

/**
 * Photo du volet : celle choisie dans les réglages, sinon la photo du lac fournie avec l'extension.
 *
 * @return string Balise img (photo décorative : texte alternatif vide).
 */
function vf_securite_habillage_photo() {
	$tailles = '(max-width: 900px) 100vw, 56vw';
	$id      = vf_securite_reglages()['porte_image'];
	if ( $id && wp_attachment_is_image( $id ) ) {
		$balise = wp_get_attachment_image(
			$id,
			'large',
			false,
			array(
				'class'         => 'vf-porte__photo',
				'alt'           => '',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'decoding'      => 'async',
				'sizes'         => $tailles,
			)
		);
		if ( $balise ) {
			return $balise;
		}
	}
	return sprintf(
		'<img class="vf-porte__photo" src="%1$s" srcset="%2$s 768w, %1$s 1200w" sizes="%3$s" width="1200" height="930" alt="" fetchpriority="high" decoding="async">',
		esc_url( vf_securite_fichier_url( 'img/lac-1200.webp' ) ),
		esc_url( vf_securite_fichier_url( 'img/lac-768.webp' ) ),
		esc_attr( $tailles )
	);
}

/**
 * Volet de gauche (en-tête de la page) : photo, logo, message d'accueil.
 * Il est suivi de l'ouverture de la zone principale, qui contient le titre et le formulaire :
 * les lecteurs d'écran disposent ainsi de deux repères, « en-tête » et « contenu principal ».
 */
function vf_securite_habillage_volet() {
	global $interim_login;
	if ( ! vf_securite_habillage_actif() ) {
		return;
	}
	$r   = vf_securite_reglages();
	$nom = get_bloginfo( 'name', 'display' );
	if ( ! $interim_login ) : // Fenêtre de reconnexion de l'éditeur : formulaire seul.
		?>
	<header class="vf-porte">
		<?php echo vf_securite_habillage_photo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- balise construite et échappée dans la fonction. ?>
		<a class="vf-porte__marque" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img src="<?php echo esc_url( vf_securite_fichier_url( 'img/logo-villefontaine-blanc.png' ) ); ?>" width="300" height="102" alt="<?php echo esc_attr( $nom ); ?>, retour au site">
		</a>
		<div class="vf-porte__accueil">
			<span class="vf-porte__feuilles" aria-hidden="true"><span></span><span></span></span>
			<?php if ( '' !== $r['porte_titre'] ) : ?>
				<p class="vf-porte__titre"><?php echo esc_html( $r['porte_titre'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $r['porte_texte'] ) : ?>
				<p class="vf-porte__texte"><?php echo esc_html( $r['porte_texte'] ); ?></p>
			<?php endif; ?>
			<p class="vf-porte__note">Accès réservé aux agents habilités. Les connexions sont enregistrées.</p>
		</div>
	</header>
		<?php
	endif;
	echo '<main class="vf-porte__principal">' . "\n";
}
add_action( 'login_header', 'vf_securite_habillage_volet' );

/**
 * Fermeture de la zone principale, avant les scripts de fin de page.
 */
function vf_securite_habillage_fin() {
	if ( vf_securite_habillage_actif() ) {
		echo '</main>' . "\n";
	}
}
add_action( 'login_footer', 'vf_securite_habillage_fin', 1 );

/**
 * Titre de l'onglet du navigateur : « Se connecter | Ville de Villefontaine » (sans mention de WordPress).
 *
 * @param string $titre_complet Titre d'origine.
 * @param string $titre         Titre de la vue (Se connecter, Mot de passe oublié…).
 * @return string
 */
function vf_securite_habillage_titre( $titre_complet, $titre = '' ) {
	if ( ! vf_securite_habillage_actif() || '' === (string) $titre ) {
		return $titre_complet;
	}
	return $titre . ' | ' . get_bloginfo( 'name', 'display' );
}
add_filter( 'login_title', 'vf_securite_habillage_titre', 10, 2 );

/**
 * Le lien du logo mène au site, pas à wordpress.org.
 *
 * @param string $url Adresse d'origine.
 * @return string
 */
function vf_securite_habillage_lien_logo( $url ) {
	return vf_securite_habillage_actif() ? home_url( '/' ) : $url;
}
add_filter( 'login_headerurl', 'vf_securite_habillage_lien_logo' );

/**
 * Texte du lien du logo.
 *
 * @param string $texte Texte d'origine.
 * @return string
 */
function vf_securite_habillage_texte_logo( $texte ) {
	return vf_securite_habillage_actif() ? get_bloginfo( 'name', 'display' ) : $texte;
}
add_filter( 'login_headertext', 'vf_securite_habillage_texte_logo' );

/**
 * Pas de sélecteur de langue : l'administration est en français.
 *
 * @param bool $afficher Afficher le sélecteur ?
 * @return bool
 */
function vf_securite_habillage_sans_langues( $afficher ) {
	return vf_securite_habillage_actif() ? false : $afficher;
}
add_filter( 'login_display_language_dropdown', 'vf_securite_habillage_sans_langues' );

/**
 * Lien de retour vers le site, sous le formulaire.
 *
 * @param string $lien Lien d'origine.
 * @return string
 */
function vf_securite_habillage_retour( $lien ) {
	if ( ! vf_securite_habillage_actif() ) {
		return $lien;
	}
	return '<a href="' . esc_url( home_url( '/' ) ) . '"><span aria-hidden="true">←</span> Retour au site de la Ville</a>';
}
add_filter( 'login_site_html_link', 'vf_securite_habillage_retour' );
