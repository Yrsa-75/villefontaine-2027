<?php
/**
 * Bloc « Chiffres clés ».
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_c = array_filter(
	(array) $attributes['chiffres'],
	static function ( $c ) {
		return '' !== trim( (string) ( $c['nombre'] ?? '' ) );
	}
);
if ( ! $vf_c ) {
	return;
}
?>
<ul class="vf-chiffres vf-chiffres--<?php echo (int) min( 4, count( $vf_c ) ); ?>">
	<?php foreach ( $vf_c as $vf_i ) : ?>
	<li class="apparition"><strong><?php echo esc_html( $vf_i['nombre'] ); ?></strong><span><?php echo wp_kses( $vf_i['libelle'] ?? '', array( 'sup' => array(), 'sub' => array() ) ); ?></span></li>
	<?php endforeach; ?>
</ul>
