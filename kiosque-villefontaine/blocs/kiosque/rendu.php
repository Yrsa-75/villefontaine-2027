<?php
/**
 * Bloc « Kiosque ».
 *
 * RGPD : aucun contenu Calaméo n'est chargé à l'affichage. Le lecteur s'ouvre dans une fenêtre
 * qui demande d'abord l'accord du visiteur (vue.js) ; le PDF reste proposé sans service externe.
 *
 * @package KiosqueVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_format = in_array( $attributes['format'], array( 'eventail', 'grille', 'seule' ), true ) ? $attributes['format'] : 'eventail';
if ( 'seule' === $vf_format ) {
	$vf_id    = ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
	$vf_pubs  = ( $vf_id && 'vf_publication' === get_post_type( $vf_id ) ) ? array( get_post( $vf_id ) ) : array();
} else {
	$vf_args = array(
		'post_type'      => 'vf_publication',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, min( 'grille' === $vf_format ? 48 : 7, (int) $attributes['nombre'] ) ),
		'no_found_rows'  => true,
	);
	if ( $attributes['type'] ) {
		$vf_args['tax_query'] = array( array( 'taxonomy' => 'vf_type_publication', 'terms' => (int) $attributes['type'] ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$vf_pubs = get_posts( $vf_args );
}
if ( ! $vf_pubs ) {
	return;
}

$vf_n    = count( $vf_pubs );
$vf_cartes = '';
foreach ( $vf_pubs as $vf_i => $vf_p ) {
	$vf_titre = get_the_title( $vf_p );
	$vf_code  = vf_kiosque_code_calameo( get_post_meta( $vf_p->ID, 'vf_calameo', true ) );
	$vf_pdf   = (string) get_post_meta( $vf_p->ID, 'vf_pdf', true );
	$vf_lien  = get_permalink( $vf_p );
	$vf_couv  = get_the_post_thumbnail( $vf_p, 'medium_large', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 760px) 62vw, 210px' ) );
	if ( ! $vf_couv ) {
		$vf_couv = '<span class="publication__sans-couv" aria-hidden="true">' . esc_html( $vf_titre ) . '</span>';
	}
	$vf_style = '';
	if ( 'eventail' === $vf_format ) {
		$vf_o     = $vf_i - ( $vf_n - 1 ) / 2;
		$vf_style = sprintf( ' style="--ry: %sdeg; --tz: %dpx; --ty: %dpx; --z: %d"', number_format( -$vf_o * 8.8, 1, '.', '' ), (int) round( -40 * abs( $vf_o ) ), (int) round( 10 * abs( $vf_o ) ), (int) round( 10 - 2 * abs( $vf_o ) ) );
	}
	$vf_data = $vf_code ? ' data-calameo="' . esc_attr( $vf_code ) . '" data-titre="' . esc_attr( $vf_titre ) . '"' . ( $vf_pdf ? ' data-pdf="' . esc_url( $vf_pdf ) . '"' : '' ) : '';
	$vf_h    = ( 'seule' === $vf_format || 2 === (int) ( $attributes['niveau'] ?? 3 ) ) ? 'h2' : 'h3';

	$vf_cartes .= '<li' . $vf_style . '><article class="publication">';
	$vf_cartes .= '<a class="publication__couv" href="' . esc_url( $vf_code || ! $vf_pdf ? $vf_lien : $vf_pdf ) . '"' . $vf_data . ' aria-label="' . esc_attr( 'Lire ' . $vf_titre . ( $vf_code ? ' en ligne' : '' ) ) . '">' . $vf_couv . '</a>';
	$vf_cartes .= '<' . $vf_h . ' class="publication__titre">' . esc_html( $vf_titre ) . '</' . $vf_h . '><p>';
	if ( $vf_code ) {
		$vf_cartes .= '<a href="' . esc_url( $vf_lien ) . '"' . $vf_data . '>Lire en ligne<span class="visuellement-masque"> : ' . esc_html( $vf_titre ) . '</span></a>';
	}
	if ( $vf_pdf ) {
		$vf_cartes .= '<a href="' . esc_url( $vf_pdf ) . '" download>PDF<span class="visuellement-masque"> : ' . esc_html( $vf_titre ) . ', télécharger</span></a>';
	}
	$vf_cartes .= '</p></article></li>';
}
$vf_classe = array( 'eventail' => 'eventail apparition', 'grille' => 'kiosque-grille', 'seule' => 'kiosque-seule' )[ $vf_format ];
?>
<ul class="<?php echo esc_attr( $vf_classe ); ?>" data-kiosque><?php echo $vf_cartes; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé lors de la construction. ?></ul>
