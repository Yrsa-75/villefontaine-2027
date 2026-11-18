<?php
/**
 * Bloc « Carte contact » (dans le contenu).
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf = $attributes;
if ( ! $vf['adresse'] && ! $vf['tel'] && ! $vf['courriel'] && ! $vf['texte'] && ! $vf['lien'] ) {
	return;
}
$vf_tel = preg_replace( '/[^0-9+]/', '', (string) $vf['tel'] );
$vf_tel = ( $vf_tel && '0' === $vf_tel[0] ) ? '+33' . substr( $vf_tel, 1 ) : $vf_tel;
?>
<div class="vf-contact-carte">
	<?php if ( $vf['titre'] ) : ?><p class="vf-contact-carte__titre"><?php echo esc_html( $vf['titre'] ); ?></p><?php endif; ?>
	<?php if ( $vf['texte'] ) : ?><p><?php echo esc_html( $vf['texte'] ); ?></p><?php endif; ?>
	<ul>
		<?php if ( $vf['adresse'] ) : ?><li><?php echo vf_socle_icone( 'lieu', 20, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo nl2br( esc_html( $vf['adresse'] ) ); ?></span></li><?php endif; ?>
		<?php if ( $vf['tel'] ) : ?><li><?php echo vf_socle_icone( 'tel', 20, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="tel:<?php echo esc_attr( $vf_tel ); ?>"><?php echo esc_html( $vf['tel'] ); ?></a></li><?php endif; ?>
		<?php if ( $vf['courriel'] && is_email( $vf['courriel'] ) ) : ?><li><?php echo vf_socle_icone( 'courriel', 20, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="mailto:<?php echo esc_attr( antispambot( $vf['courriel'] ) ); ?>"><?php echo esc_html( antispambot( $vf['courriel'] ) ); ?></a></li><?php endif; ?>
		<?php if ( $vf['horaires'] ) : ?><li><?php echo vf_socle_icone( 'horloge', 20, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo nl2br( esc_html( $vf['horaires'] ) ); ?></span></li><?php endif; ?>
		<?php if ( $vf['lien'] ) : ?><li><?php echo vf_socle_icone( 'fleche', 20, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="<?php echo esc_url( $vf['lien'] ); ?>"<?php echo vf_socle_attributs_lien( $vf['lien'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>Site internet<?php echo vf_socle_mention_lien( $vf['lien'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li><?php endif; ?>
	</ul>
</div>
