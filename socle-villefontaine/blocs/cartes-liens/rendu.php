<?php
/**
 * Bloc « Cartes de liens » : image (ou icône), titre, texte, lien.
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_cartes = array_filter(
	(array) $attributes['cartes'],
	static function ( $c ) {
		return ! empty( $c['titre'] );
	}
);
if ( ! $vf_cartes ) {
	return;
}
$vf_col = max( 2, min( 4, (int) $attributes['colonnes'] ) );
?>
<ul class="vf-cartes vf-cartes--<?php echo (int) $vf_col; ?>">
	<?php
	foreach ( array_values( $vf_cartes ) as $vf_i => $vf_c ) :
		$vf_url = trim( (string) ( $vf_c['url'] ?? '' ) );
		$vf_img = '';
		if ( ! empty( $vf_c['imageId'] ) ) {
			$vf_img = wp_get_attachment_image( (int) $vf_c['imageId'], 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 760px) 100vw, 33vw' ) );
		} elseif ( ! empty( $vf_c['image'] ) ) {
			$vf_img = '<img src="' . esc_url( $vf_c['image'] ) . '" alt="" loading="lazy" decoding="async">';
		}
		?>
	<li class="vf-carte-lien apparition<?php echo $vf_img ? '' : ' vf-carte-lien--icone'; ?>" style="--delai: <?php echo esc_attr( number_format( ( $vf_i % 3 ) * 0.06, 2, '.', '' ) ); ?>s">
		<?php if ( $vf_img ) : ?><div class="vf-carte-lien__image"><?php echo $vf_img; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php else : ?><span class="vf-carte-lien__icone"><?php echo vf_socle_icone( ! empty( $vf_c['icone'] ) ? $vf_c['icone'] : 'fleche', 26, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
		<div class="vf-carte-lien__corps">
			<h3><?php if ( $vf_url ) : ?><a href="<?php echo esc_url( $vf_url ); ?>"<?php echo vf_socle_attributs_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $vf_c['titre'] ); ?><?php echo vf_socle_mention_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a><?php else : ?><?php echo esc_html( $vf_c['titre'] ); ?><?php endif; ?></h3>
			<?php if ( ! empty( $vf_c['texte'] ) ) : ?><p><?php echo esc_html( $vf_c['texte'] ); ?></p><?php endif; ?>
		</div>
		<?php if ( $vf_url ) : ?><span class="vf-carte-lien__fleche" aria-hidden="true"><?php echo vf_socle_icone( 'fleche', 18, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
	</li>
	<?php endforeach; ?>
</ul>
