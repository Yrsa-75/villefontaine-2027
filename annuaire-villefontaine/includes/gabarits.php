<?php
/**
 * Gabarit de la fiche d'un lieu (reprend l'en-tête et le pied du thème).
 *
 * @package AnnuaireVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Déclare le gabarit.
 */
function vf_annuaire_gabarits() {
	if ( ! function_exists( 'register_block_template' ) ) {
		return;
	}
	register_block_template(
		'annuaire-villefontaine//single-vf_lieu',
		array(
			'title'       => 'Fiche d’un lieu',
			'description' => 'Page d’un lieu de l’annuaire : coordonnées, horaires, carte, présentation.',
			'post_types'  => array( 'vf_lieu' ),
			'content'     => '<!-- wp:template-part {"slug":"header","tagName":"div","className":"vf-zone-entete"} /-->
<!-- wp:group {"tagName":"main","anchor":"contenu","className":"vf-article vf-lieu","layout":{"type":"default"}} -->
<main class="wp-block-group vf-article vf-lieu" id="contenu"><!-- wp:group {"className":"conteneur","layout":{"type":"default"}} -->
<div class="wp-block-group conteneur"><!-- wp:vf/fil-ariane /-->

<!-- wp:group {"className":"vf-article__tete","layout":{"type":"default"}} -->
<div class="wp-block-group vf-article__tete"><!-- wp:post-title {"level":1} /-->

<!-- wp:post-excerpt /--></div>
<!-- /wp:group -->

<!-- wp:vf/lieu-fiche /-->

<!-- wp:post-content {"className":"contenu vf-article__corps","layout":{"type":"default"}} /--></div>
<!-- /wp:group --></main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"div","className":"vf-zone-pied"} /-->',
		)
	);
}
add_action( 'init', 'vf_annuaire_gabarits', 30 );
