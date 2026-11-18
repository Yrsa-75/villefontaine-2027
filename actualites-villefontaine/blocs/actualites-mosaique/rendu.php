<?php
/**
 * Bloc « Actualités en mosaïque ».
 *
 * @package ActualitesVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_nombre = in_array( (int) $attributes['nombre'], array( 3, 6 ), true ) ? (int) $attributes['nombre'] : 6;
$vf_actus  = vf_actus_selection( $vf_nombre, (int) $attributes['categorie'] );
if ( ! $vf_actus ) {
	return;
}
$vf_lettres = array( 'une', 'a', 'b', 'c', 'd', 'e' );
?>
<div class="mosaique mosaique--<?php echo (int) count( $vf_actus ); ?>">
<?php
foreach ( $vf_actus as $vf_i => $vf_p ) :
	$vf_une   = 0 === $vf_i;
	$vf_cats  = get_the_category( $vf_p->ID );
	$vf_theme = $vf_cats ? $vf_cats[0]->name : '';
	$vf_image = get_the_post_thumbnail(
		$vf_p,
		$vf_une ? 'large' : 'medium_large',
		array(
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => $vf_une ? '(max-width: 760px) 100vw, 60vw' : '(max-width: 760px) 100vw, 33vw',
		)
	);
	$vf_classe = 'tuile-actu tuile-actu--' . $vf_lettres[ $vf_i ] . ' apparition' . ( $vf_image ? '' : ' tuile-actu--sans-image' );
	?>
<article class="<?php echo esc_attr( $vf_classe ); ?>">
	<?php echo $vf_image ? $vf_image : '<span class="tuile-actu__fond" aria-hidden="true"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- produit par WordPress. ?>
	<?php if ( $vf_une && get_post_meta( $vf_p->ID, 'vf_a_la_une', true ) ) : ?>
	<span class="pastille-une"><?php echo vf_socle_icone( 'etoile', 14, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> À la une</span>
	<?php endif; ?>
	<span class="fleche-coin" aria-hidden="true"><?php echo vf_socle_icone( 'fleche', 18, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
	<div class="tuile-actu__corps">
		<?php if ( $vf_theme ) : ?><p class="tuile-actu__theme"><?php echo esc_html( $vf_theme ); ?></p><?php endif; ?>
		<h3><a href="<?php echo esc_url( get_permalink( $vf_p ) ); ?>"><?php echo esc_html( get_the_title( $vf_p ) ); ?></a></h3>
		<?php if ( $vf_une ) : ?>
		<p class="resume"><?php echo esc_html( wp_trim_words( get_the_excerpt( $vf_p ), 32 ) ); ?></p>
		<?php endif; ?>
	</div>
</article>
<?php endforeach; ?>
</div>
