<?php
/**
 * Title: Page de rubrique
 * Slug: villefontaine/page-rubrique
 * Categories: villefontaine-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1200
 * Description: Page qui présente une rubrique : tuiles automatiques vers ses sous-pages, encadré « À savoir », texte libre.
 *
 * @package Villefontaine
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:vf/sous-pages {"lock":{"move":false,"remove":true}} /-->

<!-- wp:group {"className":"is-style-a-savoir","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-a-savoir"><!-- wp:heading -->
<h2 class="wp-block-heading">À savoir</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"placeholder":"Une information importante pour les habitants (inscription, date limite, condition)…"} -->
<p></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Présentation</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"placeholder":"Présentez la rubrique en quelques phrases…"} -->
<p></p>
<!-- /wp:paragraph -->
