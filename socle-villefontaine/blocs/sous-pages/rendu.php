<?php
/**
 * Bloc « Pages de la rubrique » : tuiles vers les sous-pages publiées, dans l'ordre du menu « Attributs > Ordre ».
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_id     = vf_socle_post_du_bloc( $block );
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
?>
<section class="bloc-contenu vf-sous-pages" aria-labelledby="<?php echo esc_attr( $vf_titre_id ); ?>">
<h2 id="<?php echo esc_attr( $vf_titre_id ); ?>"><?php echo esc_html( $attributes['titre'] ? $attributes['titre'] : 'Dans cette rubrique' ); ?></h2>
<ul class="tuiles">
	<?php
	foreach ( $vf_enfants as $vf_i => $vf_p ) :
		$vf_resume = has_excerpt( $vf_p ) ? get_the_excerpt( $vf_p ) : '';
		?>
	<li class="apparition" style="--delai: <?php echo esc_attr( number_format( min( $vf_i, 8 ) * 0.06, 2, '.', '' ) ); ?>s"><a class="tuile" href="<?php echo esc_url( get_permalink( $vf_p ) ); ?>"><span class="tuile__icone"><?php echo vf_socle_icone( 'doc', 26, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><span class="tuile__titre"><?php echo esc_html( get_the_title( $vf_p ) ); ?> <?php echo vf_socle_icone( 'fleche', 18, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<?php if ( $vf_resume ) : ?><span class="tuile__resume"><?php echo esc_html( wp_trim_words( $vf_resume, 22 ) ); ?></span><?php endif; ?>
	</span></a></li>
	<?php endforeach; ?>
</ul>
</section>
