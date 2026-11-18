<?php
/**
 * Bloc « Fil d'Ariane ».
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_html = vf_socle_html_ariane( is_singular() ? vf_socle_post_du_bloc( $block ) : 0 );
if ( '' === $vf_html && vf_socle_apercu_editeur() ) {
	$vf_html = '<nav class="ariane" aria-label="Fil d’Ariane"><ol><li><a href="#">Accueil</a></li><li aria-current="page">Page en cours</li></ol></nav>';
}
echo $vf_html; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction.
