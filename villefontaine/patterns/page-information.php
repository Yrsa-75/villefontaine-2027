<?php
/**
 * Title: Page d’information (service, démarche)
 * Slug: villefontaine/page-information
 * Categories: villefontaine-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1200
 * Description: Page type pour un service ou une démarche : introduction, intertitres (repris dans le sommaire), liste à coches, encadré, bouton d’action.
 *
 * @package Villefontaine
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:paragraph {"fontSize":"moyen","placeholder":"Introduction : en deux phrases, à quoi sert ce service et pour qui."} -->
<p class="has-moyen-font-size"></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Qui est concerné ?</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"is-style-coches"} -->
<ul class="wp-block-list is-style-coches"><!-- wp:list-item -->
<li></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Comment faire ?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"placeholder":"Les étapes, les pièces à fournir…"} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Faire la démarche en ligne</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"is-style-a-savoir","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-a-savoir"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">À savoir</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"placeholder":"Délai, coût, condition particulière…"} -->
<p></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
