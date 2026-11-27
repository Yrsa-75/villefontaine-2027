<?php
/**
 * Title: Pied de page
 * Slug: villefontaine/pied
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 * Description: Pied de page du site : coordonnées, horaires, réseaux sociaux et liens légaux.
 *
 * @package Villefontaine
 */

defined( 'ABSPATH' ) || exit;

$vf_logo = get_theme_file_uri( 'assets/img/logo-villefontaine-blanc.png' );
?>
<!-- wp:group {"tagName":"footer","className":"pied","layout":{"type":"default"}} -->
<footer class="wp-block-group pied"><!-- wp:group {"className":"conteneur pied__grille","layout":{"type":"default"}} -->
<div class="wp-block-group conteneur pied__grille"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"190px","height":"65px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( $vf_logo ); ?>" alt="Ville de Villefontaine" style="width:190px;height:65px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>Hôtel de ville<br>Place Pierre Mendès France<br>BP 88, 38093 Villefontaine Cedex</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"pied__tel-bloc"} -->
<p class="pied__tel-bloc"><a class="pied__tel" href="tel:+33474960000">04 74 96 00 00</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Horaires d’accueil</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Lundi, mardi, mercredi et vendredi : 8 h 30 à 12 h et 13 h 30 à 17 h<br>Jeudi : 10 h 30 à 12 h et 13 h 30 à 17 h<br>1er samedi du mois : 8 h 30 à 12 h</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Suivez-nous</h2>
<!-- /wp:heading -->

<!-- wp:social-links {"openInNewTab":true,"className":"is-style-logos-only"} -->
<ul class="wp-block-social-links is-style-logos-only"><!-- wp:social-link {"url":"https://www.facebook.com/VilledeVillefontaine/","service":"facebook","label":"Facebook de la ville"} /-->

<!-- wp:social-link {"url":"https://www.instagram.com/villedevillefontaine/","service":"instagram","label":"Instagram de la ville"} /-->

<!-- wp:social-link {"url":"https://www.youtube.com/channel/UCdWo26SRWQdrvBMrQLtw6GQ","service":"youtube","label":"YouTube de la ville"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"pied__bas","layout":{"type":"default"}} -->
<div class="wp-block-group pied__bas"><!-- wp:group {"className":"conteneur","layout":{"type":"default"}} -->
<div class="wp-block-group conteneur"><!-- wp:paragraph -->
<p><a href="/accessibilite-du-site/">Accessibilité : partiellement conforme</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="/mentions-legales/">Mentions légales</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="/donnees-personnelles/">Données personnelles</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="/plan-du-site/">Plan du site</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="/charte-graphique/">Charte graphique</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></footer>
<!-- /wp:group -->
