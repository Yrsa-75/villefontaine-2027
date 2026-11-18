<?php
/**
 * Bloc « Trombinoscope » : portraits avec nom, fonction et mention facultative.
 * En mode « vedette », une seule personne est mise en avant (ex. le maire).
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_personnes = array_values(
	array_filter(
		(array) $attributes['personnes'],
		static function ( $p ) {
			return ! empty( $p['nom'] );
		}
	)
);
if ( ! $vf_personnes ) {
	return;
}
$vf_vedette = ! empty( $attributes['vedette'] );
$vf_titre   = trim( (string) $attributes['titre'] );
$vf_id_t    = $vf_titre ? wp_unique_id( 'vf-trombi-' ) : '';

/**
 * Portrait d'une personne : image de la médiathèque (tailles adaptées) ou adresse simple.
 */
$vf_portrait = static function ( $p, $taille ) {
	$id = ! empty( $p['imageId'] ) ? (int) $p['imageId'] : 0;
	if ( ! $id && ! empty( $p['image'] ) ) {
		$id = attachment_url_to_postid( $p['image'] );
	}
	if ( $id ) {
		return wp_get_attachment_image( $id, $taille, false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 600px) 50vw, 240px' ) );
	}
	if ( ! empty( $p['image'] ) ) {
		return '<img src="' . esc_url( $p['image'] ) . '" alt="" loading="lazy" decoding="async">';
	}
	// Pas de photo : initiales.
	$mots      = preg_split( '/\s+/', trim( wp_strip_all_tags( $p['nom'] ) ) );
	$initiales = '';
	foreach ( array_slice( $mots, 0, 2 ) as $m ) {
		$initiales .= mb_strtoupper( mb_substr( $m, 0, 1 ) );
	}
	return '<span class="vf-trombi__initiales" aria-hidden="true">' . esc_html( $initiales ) . '</span>';
};
?>
<section class="vf-trombi<?php echo $vf_vedette ? ' vf-trombi--vedette' : ''; ?>"<?php echo ! empty( $attributes['anchor'] ) ? ' id="' . esc_attr( $attributes['anchor'] ) . '"' : ''; ?><?php echo $vf_id_t ? ' aria-labelledby="' . esc_attr( $vf_id_t ) . '"' : ''; ?>>
	<?php if ( $vf_titre ) : ?>
		<h2 class="vf-trombi__titre" id="<?php echo esc_attr( $vf_id_t ); ?>"><?php echo esc_html( $vf_titre ); ?> <span class="vf-trombi__nombre">(<?php echo count( $vf_personnes ); ?>)</span></h2>
	<?php endif; ?>
	<ul class="vf-trombi__liste" role="list">
		<?php foreach ( $vf_personnes as $vf_i => $vf_p ) : ?>
		<li class="vf-trombi__personne apparition" style="--delai: <?php echo esc_attr( number_format( ( $vf_i % 4 ) * 0.05, 2, '.', '' ) ); ?>s">
			<div class="vf-trombi__photo"><?php echo $vf_portrait( $vf_p, $vf_vedette ? 'medium_large' : 'medium' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="vf-trombi__texte">
				<p class="vf-trombi__nom"><?php echo esc_html( $vf_p['nom'] ); ?></p>
				<?php if ( ! empty( $vf_p['fonction'] ) ) : ?><p class="vf-trombi__fonction"><?php echo esc_html( $vf_p['fonction'] ); ?></p><?php endif; ?>
				<?php if ( ! empty( $vf_p['mention'] ) ) : ?><p class="vf-trombi__mention"><?php echo esc_html( $vf_p['mention'] ); ?></p><?php endif; ?>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>
</section>
