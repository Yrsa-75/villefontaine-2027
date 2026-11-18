<?php
/**
 * Gabarits fournis par le plugin (page d'un événement, page Agenda).
 * Ils restent modifiables dans Apparence > Éditeur si besoin, et reprennent l'en-tête et le pied du thème.
 *
 * @package AgendaVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Déclare les gabarits.
 */
function vf_agenda_gabarits() {
	if ( ! function_exists( 'register_block_template' ) ) {
		return;
	}
	$entete = '<!-- wp:template-part {"slug":"header","tagName":"div","className":"vf-zone-entete"} /-->';
	$pied   = '<!-- wp:template-part {"slug":"footer","tagName":"div","className":"vf-zone-pied"} /-->';

	register_block_template(
		'agenda-villefontaine//single-vf_evenement',
		array(
			'title'       => 'Événement de l’agenda',
			'description' => 'Page d’un événement : date, horaires, lieu, description.',
			'post_types'  => array( 'vf_evenement' ),
			'content'     => $entete . '
<!-- wp:group {"tagName":"main","anchor":"contenu","className":"vf-article vf-evenement","layout":{"type":"default"}} -->
<main class="wp-block-group vf-article vf-evenement" id="contenu"><!-- wp:group {"className":"conteneur","layout":{"type":"default"}} -->
<div class="wp-block-group conteneur"><!-- wp:vf/fil-ariane /-->

<!-- wp:group {"className":"vf-article__tete","layout":{"type":"default"}} -->
<div class="wp-block-group vf-article__tete"><!-- wp:post-terms {"term":"vf_theme_evenement","className":"section__surtitre"} /-->

<!-- wp:post-title {"level":1} /-->

<!-- wp:vf/evenement-infos /--></div>
<!-- /wp:group -->

<!-- wp:post-featured-image {"className":"vf-article__image","sizeSlug":"vf-bandeau"} /-->

<!-- wp:post-content {"className":"contenu vf-article__corps","layout":{"type":"default"}} /--></div>
<!-- /wp:group --></main>
<!-- /wp:group -->
' . $pied,
		)
	);

	register_block_template(
		'agenda-villefontaine//archive-vf_evenement',
		array(
			'title'       => 'Agenda',
			'description' => 'Page Agenda : tous les événements à venir, par mois, avec filtres.',
			'post_types'  => array( 'vf_evenement' ),
			'content'     => $entete . '
<!-- wp:group {"tagName":"main","anchor":"contenu","className":"vf-liste","layout":{"type":"default"}} -->
<main class="wp-block-group vf-liste" id="contenu"><!-- wp:group {"className":"conteneur","layout":{"type":"default"}} -->
<div class="wp-block-group conteneur"><!-- wp:vf/fil-ariane /-->

<!-- wp:paragraph {"className":"section__surtitre"} -->
<p class="section__surtitre">À ne pas manquer</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Agenda</h1>
<!-- /wp:heading -->

<!-- wp:vf/agenda-liste /--></div>
<!-- /wp:group --></main>
<!-- /wp:group -->
' . $pied,
		)
	);
}
add_action( 'init', 'vf_agenda_gabarits', 30 );
