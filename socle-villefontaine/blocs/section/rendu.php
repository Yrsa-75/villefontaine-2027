<?php
/**
 * Bloc « Section avec titre » : en-tête de section + blocs intérieurs ($content).
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Blocs intérieurs déjà affichés.
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

if ( '' === trim( (string) $content ) ) {
	return; // Section vide (ex. bloc d'un plugin pas encore installé) : on n'affiche rien.
}

$vf_titre = trim( wp_strip_all_tags( (string) $attributes['titre'] ) );
$vf_id    = wp_unique_id( 'section-' );
$vf_url   = trim( (string) $attributes['lienUrl'] );

ob_start();
?>
<div class="section__tete apparition"><div>
<?php if ( '' !== trim( (string) $attributes['surtitre'] ) ) : ?>
<p class="section__surtitre"><?php echo esc_html( wp_strip_all_tags( $attributes['surtitre'] ) ); ?></p>
<?php endif; ?>
<?php if ( '' !== $vf_titre ) : ?>
<h2 id="<?php echo esc_attr( $vf_id ); ?>"><?php echo esc_html( $vf_titre ); ?></h2>
<?php endif; ?>
</div>
<?php if ( '' !== trim( (string) $attributes['lienTexte'] ) && '' !== $vf_url ) : ?>
<a class="lien-fleche" href="<?php echo esc_url( $vf_url ); ?>"<?php echo vf_socle_attributs_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><span><?php echo esc_html( $attributes['lienTexte'] ); ?></span> <?php echo vf_socle_icone( 'fleche', 18, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo vf_socle_mention_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
<?php endif; ?>
</div>
<?php
$vf_tete    = ob_get_clean();
$vf_label   = '' !== $vf_titre ? ' aria-labelledby="' . esc_attr( $vf_id ) . '"' : '';
$vf_contenu = $vf_tete . $content;

if ( 'bleu' === $attributes['fond'] ) {
	echo '<section class="bande"' . $vf_label . '><div class="conteneur">' . $vf_contenu . '</div></section>'; // phpcs:ignore WordPress.Security.EscapeOutput -- éléments échappés ci-dessus ; $content = blocs déjà filtrés par WordPress.
} else {
	echo '<section class="conteneur section"' . $vf_label . '>' . $vf_contenu . '</section>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
