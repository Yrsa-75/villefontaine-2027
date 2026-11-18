<?php
/**
 * Title: Page d’accueil
 * Slug: villefontaine/accueil
 * Categories: villefontaine-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1440
 * Description: Grand visuel, accès en 1 clic, actualités, agenda, kiosque et carte des services.
 *
 * @package Villefontaine
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:vf/heros {"lock":{"move":true,"remove":true}} /-->

<!-- wp:vf/acces-rapides {"lock":{"move":true,"remove":true}} /-->

<!-- wp:vf/section {"surtitre":"Ce qui se passe","titre":"Actualités","lienTexte":"Toutes les actualités","lienUrl":"/actualites/"} -->
<!-- wp:vf/actualites-mosaique {"lock":{"move":false,"remove":true}} /-->
<!-- /wp:vf/section -->

<!-- wp:vf/section {"surtitre":"À ne pas manquer","titre":"Agenda","lienTexte":"Tout l’agenda","lienUrl":"/agenda/","fond":"bleu"} -->
<!-- wp:vf/agenda-prochains {"lock":{"move":false,"remove":true}} /-->
<!-- /wp:vf/section -->

<!-- wp:vf/section {"surtitre":"Publications","titre":"Kiosque","lienTexte":"Toutes les publications","lienUrl":"/kiosque/"} -->
<!-- wp:vf/kiosque {"lock":{"move":false,"remove":true}} /-->
<!-- /wp:vf/section -->

<!-- wp:vf/carte-services {"lienTexte":"Ouvrir la carte en grand","lienUrl":"/carte-des-services/"} /-->
