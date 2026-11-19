<?php
/**
 * Corrections d'accessibilité et de validité appliquées à l'affichage.
 *
 * Ces filtres protègent le site même quand un contenu est saisi par un agent
 * sans connaître les règles du RGAA (tableaux, images).
 *
 * @package VF_Socle
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tableaux : associe chaque en-tête à sa colonne ou à sa ligne (RGAA 5.7).
 *
 * Les en-têtes de la première ligne (thead) reçoivent scope="col".
 * Un en-tête placé en début de ligne dans le corps reçoit scope="row".
 *
 * @param string $contenu HTML du bloc.
 * @return string
 */
function vf_socle_tableau_entetes( $contenu ) {
	if ( false === stripos( $contenu, '<th' ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $contenu;
	}
	$p          = new WP_HTML_Tag_Processor( $contenu );
	$dans_thead = false;
	$debut      = false;
	while ( $p->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
		$balise = $p->get_tag();
		if ( 'THEAD' === $balise ) {
			$dans_thead = ! $p->is_tag_closer();
			continue;
		}
		if ( $p->is_tag_closer() ) {
			continue;
		}
		if ( 'TR' === $balise ) {
			$debut = true;
			continue;
		}
		if ( 'TD' === $balise ) {
			$debut = false;
			continue;
		}
		if ( 'TH' === $balise ) {
			if ( null === $p->get_attribute( 'scope' ) && null === $p->get_attribute( 'id' ) ) {
				$p->set_attribute( 'scope', ( $dans_thead || ! $debut ) ? 'col' : 'row' );
			}
			$debut = false;
		}
	}
	return $p->get_updated_html();
}
add_filter( 'render_block_core/table', 'vf_socle_tableau_entetes' );

/**
 * Images : retire l'attribut sizes quand il n'y a pas de srcset (code HTML valide, RGAA 8.2).
 *
 * @param string $html Balise img produite par WordPress.
 * @return string
 */
function vf_socle_image_sans_sizes_orphelin( $html ) {
	if ( false !== strpos( $html, ' sizes=' ) && false === strpos( $html, ' srcset=' ) ) {
		$html = preg_replace( '/\ssizes="[^"]*"/', '', $html );
	}
	return $html;
}
add_filter( 'wp_get_attachment_image', 'vf_socle_image_sans_sizes_orphelin' );
add_filter( 'post_thumbnail_html', 'vf_socle_image_sans_sizes_orphelin' );

/**
 * Images légendées : relie la légende à l'image (RGAA 1.9).
 *
 * La balise figure reçoit role="group" et un aria-label identique à la légende.
 *
 * @param string $contenu HTML du bloc image.
 * @return string
 */
function vf_socle_image_legendee( $contenu ) {
	if ( false === stripos( $contenu, '<figcaption' ) || ! preg_match( '#<figcaption[^>]*>(.*?)</figcaption>#is', $contenu, $m ) ) {
		return $contenu;
	}
	$legende = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $m[1] ) ) );
	if ( '' === $legende || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $contenu;
	}
	$p = new WP_HTML_Tag_Processor( $contenu );
	if ( $p->next_tag( 'figure' ) ) {
		if ( null === $p->get_attribute( 'role' ) ) {
			$p->set_attribute( 'role', 'group' );
		}
		if ( null === $p->get_attribute( 'aria-label' ) ) {
			$p->set_attribute( 'aria-label', $legende );
		}
	}
	return $p->get_updated_html();
}
add_filter( 'render_block_core/image', 'vf_socle_image_legendee' );

/**
 * Extrait vide : rien n'est affiché plutôt qu'un paragraphe vide (RGAA 8.9).
 *
 * @param string $contenu HTML du bloc extrait.
 * @return string
 */
function vf_socle_extrait_vide( $contenu ) {
	return '' === trim( html_entity_decode( wp_strip_all_tags( $contenu ), ENT_QUOTES, 'UTF-8' ), " \t\n\r\0\x0B\xC2\xA0" ) ? '' : $contenu;
}
add_filter( 'render_block_core/post-excerpt', 'vf_socle_extrait_vide' );
