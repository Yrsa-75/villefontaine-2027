<?php
/**
 * Bloc « En-tête du site » : logo, méga-menu (3 niveaux), actions, menu mobile.
 *
 * Accessibilité (RGAA) : boutons avec aria-expanded/aria-controls, fermeture par Échap,
 * navigation au clavier, menu mobile en boîte de dialogue, liens sortants signalés.
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_menu      = vf_socle_arbre_menu( 'vf-principal' );
$vf_logos     = vf_socle_logos();
$vf_accueil   = home_url( '/' );
$vf_demarches = has_nav_menu( 'vf-demarches' );
$vf_chevron   = vf_socle_icone( 'chevron', 16, 2.2 );
$vf_fleche    = vf_socle_icone( 'fleche', 16, 2.2 );

/**
 * Lien de menu sûr (URL échappée, nouvel onglet si sortant).
 */
$vf_lien = static function ( $el, $classe = '', $apres = '' ) {
	$url = ( '' === $el['url'] || '#' === $el['url'] ) ? '#' : $el['url'];
	return sprintf(
		'<a%s href="%s"%s%s>%s%s%s</a>',
		$classe ? ' class="' . esc_attr( $classe ) . '"' : '',
		esc_url( $url ),
		vf_socle_attributs_lien( $url ),
		$el['courant'] ? ' aria-current="page"' : '',
		esc_html( $el['titre'] ),
		$apres,
		vf_socle_mention_lien( $url )
	);
};

$vf_logo_html = '';
if ( $vf_logos['couleur'] ) {
	$vf_logo_html .= sprintf( '<img class="logo-couleur" src="%s" alt="" width="%d" height="%d">', esc_url( $vf_logos['couleur'] ), 200, (int) round( 200 * $vf_logos['hauteur'] / max( 1, $vf_logos['largeur'] ) ) );
}
if ( $vf_logos['blanc'] ) {
	$vf_logo_html .= sprintf( '<img class="logo-blanc" src="%s" alt="" width="%d" height="%d">', esc_url( $vf_logos['blanc'] ), 200, (int) round( 200 * $vf_logos['hauteur'] / max( 1, $vf_logos['largeur'] ) ) );
}
if ( '' === $vf_logo_html ) {
	$vf_logo_html = '<span class="entete__nom">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
}
?>
<?php // Liens d'évitement (RGAA 12.7) : premiers éléments atteints au clavier, visibles au focus. ?>
<a class="lien-evitement" href="#contenu">Aller au contenu</a>
<?php if ( $vf_menu ) : ?><a class="lien-evitement" href="#menu-principal">Aller au menu</a><?php endif; ?>
<header class="entete">
<div class="conteneur entete__barre">
<a class="entete__logo" href="<?php echo esc_url( $vf_accueil ); ?>" aria-label="<?php echo esc_attr( 'Ville de Villefontaine, retour à l’accueil' ); ?>"><?php echo $vf_logo_html; // phpcs:ignore WordPress.Security.EscapeOutput -- construit et échappé ci-dessus. ?></a>
<?php if ( $vf_menu ) : ?>
<nav class="nav-principale" id="menu-principal" aria-label="Menu principal" tabindex="-1"><ul>
	<?php
	foreach ( $vf_menu as $vf_r1 ) :
		$vf_id = 'mega-' . $vf_r1['id'];
		if ( ! $vf_r1['enfants'] ) {
			echo '<li>' . $vf_lien( $vf_r1, 'nav-principale__declencheur' ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
			continue;
		}
		?>
	<li>
	<button type="button" class="nav-principale__declencheur" aria-expanded="false" aria-controls="<?php echo esc_attr( $vf_id ); ?>"><?php echo esc_html( $vf_r1['titre'] ); ?> <?php echo $vf_chevron; // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	<div class="mega" id="<?php echo esc_attr( $vf_id ); ?>">
	<div class="conteneur mega__grille">
	<div class="mega__intro">
		<?php
		if ( $vf_r1['image'] ) {
			echo wp_get_attachment_image( $vf_r1['image'], 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) );
		}
		?>
		<div><h2><?php echo esc_html( $vf_r1['titre'] ); ?></h2>
		<?php if ( $vf_r1['description'] ) : ?><p><?php echo esc_html( $vf_r1['description'] ); ?></p><?php endif; ?>
		<?php if ( '#' !== $vf_r1['url'] && '' !== $vf_r1['url'] ) : ?>
			<a class="lien-fleche" href="<?php echo esc_url( $vf_r1['url'] ); ?>"><span>Toute la rubrique</span> <?php echo $vf_fleche; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<?php endif; ?>
		</div>
	</div>
	<ul class="mega__colonnes">
		<?php foreach ( $vf_r1['enfants'] as $vf_r2 ) : ?>
		<li><?php echo $vf_lien( $vf_r2, 'mega__rang2', ' ' . $vf_fleche ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( $vf_r2['enfants'] ) : ?>
			<ul class="mega__rang3">
				<?php foreach ( $vf_r2['enfants'] as $vf_r3 ) : ?>
				<li><?php echo $vf_lien( $vf_r3 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</li>
		<?php endforeach; ?>
	</ul>
	</div>
	</div>
	</li>
	<?php endforeach; ?>
</ul></nav>
<?php endif; ?>
<div class="entete__actions">
<a class="bouton-rond" href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" aria-label="Rechercher sur le site"><?php echo vf_socle_icone( 'loupe', 20, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
<?php if ( $vf_demarches ) : ?>
<button type="button" class="bouton bouton--lueur" aria-controls="panneau-demarches" aria-expanded="false" aria-haspopup="dialog">Vos démarches <?php echo vf_socle_icone( 'fleche', 18, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
<?php endif; ?>
<?php if ( $vf_menu ) : ?>
<button type="button" class="bouton-rond burger" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="menu-mobile"><?php echo vf_socle_icone( 'burger', 22, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
<?php endif; ?>
</div>
</div>
</header>
<?php if ( $vf_menu ) : ?>
<div class="voile" aria-hidden="true"></div>
<div class="tiroir" id="menu-mobile" role="dialog" aria-modal="true" aria-label="Menu">
<div class="tiroir__haut">
	<?php if ( $vf_logos['couleur'] ) : ?>
	<img src="<?php echo esc_url( $vf_logos['couleur'] ); ?>" alt="Ville de Villefontaine" width="150" height="<?php echo (int) round( 150 * $vf_logos['hauteur'] / max( 1, $vf_logos['largeur'] ) ); ?>">
	<?php else : ?>
	<span class="entete__nom"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
	<?php endif; ?>
	<button type="button" class="bouton-rond tiroir__fermer" aria-label="Fermer le menu"><?php echo vf_socle_icone( 'croix', 20, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
</div>
<nav aria-label="Menu principal, version mobile">
	<?php foreach ( $vf_menu as $vf_r1 ) : ?>
		<?php if ( ! $vf_r1['enfants'] ) : ?>
			<?php echo $vf_lien( $vf_r1, 'tiroir__lien' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php else : ?>
		<details><summary><?php echo esc_html( $vf_r1['titre'] ); ?> <?php echo $vf_chevron; // phpcs:ignore WordPress.Security.EscapeOutput ?></summary>
			<?php if ( '#' !== $vf_r1['url'] && '' !== $vf_r1['url'] ) : ?>
				<?php echo $vf_lien( array_merge( $vf_r1, array( 'titre' => $vf_r1['titre'] . ' : toute la rubrique' ) ), 'tiroir__lien' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
			<?php foreach ( $vf_r1['enfants'] as $vf_r2 ) : ?>
				<?php if ( ! $vf_r2['enfants'] ) : ?>
					<?php echo $vf_lien( $vf_r2, 'tiroir__lien' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
				<details><summary><?php echo esc_html( $vf_r2['titre'] ); ?> <?php echo $vf_chevron; // phpcs:ignore WordPress.Security.EscapeOutput ?></summary>
				<ul>
					<?php if ( '#' !== $vf_r2['url'] && '' !== $vf_r2['url'] ) : ?>
					<li><?php echo $vf_lien( array_merge( $vf_r2, array( 'titre' => $vf_r2['titre'] . ' : présentation' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
					<?php endif; ?>
					<?php foreach ( $vf_r2['enfants'] as $vf_r3 ) : ?>
					<li><?php echo $vf_lien( $vf_r3 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
					<?php endforeach; ?>
				</ul>
				</details>
				<?php endif; ?>
			<?php endforeach; ?>
		</details>
		<?php endif; ?>
	<?php endforeach; ?>
	<?php if ( $vf_demarches ) : ?>
	<p style="margin-top: 24px"><button type="button" class="bouton bouton--lueur" aria-controls="panneau-demarches" aria-expanded="false" aria-haspopup="dialog">Vos démarches <?php echo vf_socle_icone( 'fleche', 18, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button></p>
	<?php endif; ?>
</nav>
</div>
<?php endif; ?>
