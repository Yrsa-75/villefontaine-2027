<?php
/**
 * Bloc « Pages de la rubrique » : cartes automatiques vers les sous-pages publiées
 * (ordre défini dans « Attributs > Ordre » de chaque page). Avec image mise en avant si la sous-page en a une.
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_id      = vf_socle_post_du_bloc( $block );
$vf_enfants = $vf_id ? get_pages(
	array(
		'parent'      => $vf_id,
		'sort_column' => 'menu_order,post_title',
		'post_status' => 'publish',
	)
) : array();
if ( ! $vf_enfants ) {
	if ( vf_socle_apercu_editeur() ) {
		echo '<p class="vf-vide">Pages de la rubrique : les sous-pages de cette page apparaîtront ici automatiquement.</p>';
	}
	return;
}
$vf_titre_id = wp_unique_id( 'rubrique-' );
$vf_images   = 0;
foreach ( $vf_enfants as $vf_p ) {
	$vf_images += has_post_thumbnail( $vf_p ) ? 1 : 0;
}
$vf_avec_images = $vf_images >= max( 1, (int) ceil( count( $vf_enfants ) / 2 ) );
?>
<section class="bloc-contenu vf-sous-pages" aria-labelledby="<?php echo esc_attr( $vf_titre_id ); ?>">
<?php // Le titre peut être masqué à l'écran (page faite uniquement de pavés) : il reste lu par les lecteurs d'écran. ?>
<h2 id="<?php echo esc_attr( $vf_titre_id ); ?>"<?php echo ! empty( $attributes['titreMasque'] ) ? ' class="visuellement-masque"' : ''; ?>><?php echo esc_html( $attributes['titre'] ? $attributes['titre'] : 'Dans cette rubrique' ); ?></h2>
<?php // Demande de la mairie (27/11/2026) : un pavé, une image, un titre. Ni résumé ni flèche. ?>
<?php if ( $vf_avec_images ) : ?>
<ul class="vf-sous-pages__cartes">
	<?php foreach ( $vf_enfants as $vf_i => $vf_p ) : ?>
	<li class="vf-sp-carte apparition" style="--delai: <?php echo esc_attr( number_format( ( $vf_i % 3 ) * 0.06, 2, '.', '' ) ); ?>s">
		<div class="vf-sp-carte__image"><?php echo has_post_thumbnail( $vf_p ) ? get_the_post_thumbnail( $vf_p, 'vf-carte', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 760px) 100vw, 360px' ) ) : '<span aria-hidden="true">' . vf_socle_icone( vf_socle_icone_page( $vf_p ), 36, 1.6 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<div class="vf-sp-carte__corps"><h3><a href="<?php echo esc_url( get_permalink( $vf_p ) ); ?>"><?php echo esc_html( get_the_title( $vf_p ) ); ?></a></h3></div>
	</li>
	<?php endforeach; ?>
</ul>
<?php else : ?>
<ul class="tuiles">
	<?php foreach ( $vf_enfants as $vf_i => $vf_p ) : ?>
	<li class="apparition" style="--delai: <?php echo esc_attr( number_format( min( $vf_i, 8 ) * 0.06, 2, '.', '' ) ); ?>s"><a class="tuile" href="<?php echo esc_url( get_permalink( $vf_p ) ); ?>"><span class="tuile__icone"><?php echo vf_socle_icone( vf_socle_icone_page( $vf_p ), 26, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="tuile__titre"><?php echo esc_html( get_the_title( $vf_p ) ); ?></span></a></li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>
</section>
