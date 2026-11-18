<?php
/**
 * Bloc « Accès en 1 clic ».
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_liens = array_slice(
	array_filter(
		(array) $attributes['liens'],
		static function ( $l ) {
			return ! empty( $l['libelle'] );
		}
	),
	0,
	8
);
if ( ! $vf_liens ) {
	return;
}
$vf_id = wp_unique_id( 'titre-clic-' );
?>
<section class="conteneur clic-verre clic-v1" aria-labelledby="<?php echo esc_attr( $vf_id ); ?>">
<h2 id="<?php echo esc_attr( $vf_id ); ?>" class="visuellement-masque"><?php echo esc_html( $attributes['titre'] ? $attributes['titre'] : 'Accès en 1 clic' ); ?></h2>
<ul>
	<?php
	foreach ( $vf_liens as $vf_l ) :
		$vf_url = ! empty( $vf_l['url'] ) ? $vf_l['url'] : '#';
		?>
	<li><a href="<?php echo esc_url( $vf_url ); ?>"<?php echo vf_socle_attributs_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><span class="clic__icone"><?php echo vf_socle_icone( $vf_l['icone'] ?? 'fleche', 30, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php echo esc_html( $vf_l['libelle'] ); ?><?php echo vf_socle_mention_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></a></li>
	<?php endforeach; ?>
</ul>
</section>
