<?php
/**
 * Bloc « Sommaire de la page » : liste des intertitres de niveau 2 (au moins 2).
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_titres = vf_socle_intertitres( vf_socle_post_du_bloc( $block ) );
if ( count( $vf_titres ) < 2 ) {
	if ( vf_socle_apercu_editeur() ) {
		echo '<nav class="sommaire" aria-label="Accès rapide"><h2>Accès rapide</h2><ol><li><a href="#">Premier intertitre</a></li><li><a href="#">Deuxième intertitre</a></li></ol></nav>';
	}
	return;
}
wp_enqueue_script( 'vf-socle-sommaire-vue' );
?>
<nav class="sommaire" aria-labelledby="sommaire-titre"><h2 id="sommaire-titre">Accès rapide</h2><ol>
<?php foreach ( $vf_titres as $vf_t ) : ?>
<li><a href="#<?php echo esc_attr( $vf_t['id'] ); ?>"><?php echo esc_html( $vf_t['texte'] ); ?></a></li>
<?php endforeach; ?>
</ol></nav>
