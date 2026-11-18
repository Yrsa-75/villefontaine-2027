<?php
/**
 * Bloc « Bandeau de page » : fil d'Ariane, rubrique (page parente), titre, résumé, image.
 * Tout est automatique : l'agent remplit le titre, l'extrait et l'image mise en avant de sa page.
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_post_id = vf_socle_post_du_bloc( $block );
$vf_post    = $vf_post_id ? get_post( $vf_post_id ) : null;
if ( ! $vf_post ) {
	if ( vf_socle_apercu_editeur() ) {
		echo '<div class="conteneur"><div class="bandeau-page bandeau-page--sans-image"><div class="bandeau-page__texte"><p class="section__surtitre">Rubrique</p><h1>Titre de la page</h1><p class="bandeau-page__chapo">Le résumé (extrait) de la page s’affiche ici.</p></div></div></div>';
	}
	return;
}

$vf_parent = $vf_post->post_parent ? get_the_title( $vf_post->post_parent ) : '';
$vf_chapo  = has_excerpt( $vf_post ) ? get_the_excerpt( $vf_post ) : '';
$vf_image  = has_post_thumbnail( $vf_post ) ? get_the_post_thumbnail(
	$vf_post,
	'vf-bandeau',
	array(
		'alt'           => '',
		'loading'       => 'eager',
		'fetchpriority' => 'high',
		'decoding'      => 'async',
		'sizes'         => '(max-width: 760px) 100vw, 50vw',
	)
) : '';
?>
<div class="conteneur">
<?php echo vf_socle_html_ariane( $vf_post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction. ?>
<div class="bandeau-page<?php echo $vf_image ? '' : ' bandeau-page--sans-image'; ?>">
<div class="bandeau-page__texte">
<?php if ( $vf_parent ) : ?>
<p class="section__surtitre" style="margin: 0"><?php echo esc_html( $vf_parent ); ?></p>
<?php endif; ?>
<h1><?php echo esc_html( get_the_title( $vf_post ) ); ?></h1>
<?php if ( $vf_chapo ) : ?>
<p class="bandeau-page__chapo"><?php echo esc_html( $vf_chapo ); ?></p>
<?php endif; ?>
</div>
<?php if ( $vf_image ) : ?>
<div class="bandeau-page__image"><?php echo $vf_image; // phpcs:ignore WordPress.Security.EscapeOutput -- produit par WordPress. ?></div>
<?php endif; ?>
</div>
</div>
