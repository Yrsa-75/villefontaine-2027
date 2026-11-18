<?php
/**
 * Bloc « Encart contact de la page » : affiche les coordonnées saisies dans le panneau
 * « Encart contact » de l'éditeur. Rien n'est affiché si aucun champ n'est rempli.
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_id = vf_socle_post_du_bloc( $block );
$vf_c  = array();
foreach ( array_keys( vf_socle_champs_contact() ) as $vf_cle ) {
	$vf_c[ str_replace( 'vf_contact_', '', $vf_cle ) ] = $vf_id ? get_post_meta( $vf_id, $vf_cle, true ) : '';
}
$vf_rempli = $vf_c['adresse'] || $vf_c['tel'] || $vf_c['courriel'] || $vf_c['lien'] || $vf_c['horaires'];
if ( ! $vf_rempli ) {
	if ( vf_socle_apercu_editeur() ) {
		echo '<section class="contact"><h2>Contact</h2><p>Remplissez le panneau « Encart contact » de la colonne de droite pour afficher les coordonnées ici.</p></section>';
	}
	return;
}
$vf_titre = $vf_c['titre'] ? $vf_c['titre'] : 'Contact';
$vf_tel   = preg_replace( '/[^0-9+]/', '', (string) $vf_c['tel'] );
if ( $vf_tel && '0' === $vf_tel[0] ) {
	$vf_tel = '+33' . substr( $vf_tel, 1 );
}
?>
<section class="contact" aria-labelledby="contact-titre">
<h2 id="contact-titre"><?php echo esc_html( $vf_titre ); ?></h2>
<?php if ( $vf_c['adresse'] ) : ?>
<p><?php echo nl2br( esc_html( $vf_c['adresse'] ) ); ?></p>
<?php endif; ?>
<?php if ( $vf_c['tel'] ) : ?>
<p><a class="contact__tel" href="tel:<?php echo esc_attr( $vf_tel ); ?>"><?php echo esc_html( $vf_c['tel'] ); ?></a></p>
<?php endif; ?>
<?php if ( $vf_c['courriel'] && is_email( $vf_c['courriel'] ) ) : ?>
<p><a href="mailto:<?php echo esc_attr( antispambot( $vf_c['courriel'] ) ); ?>"><?php echo esc_html( antispambot( $vf_c['courriel'] ) ); ?></a></p>
<?php endif; ?>
<?php if ( $vf_c['lien'] ) : ?>
<p><a href="<?php echo esc_url( $vf_c['lien'] ); ?>"<?php echo vf_socle_attributs_lien( $vf_c['lien'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>Écrire au service<?php echo vf_socle_mention_lien( $vf_c['lien'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></p>
<?php endif; ?>
<?php if ( $vf_c['horaires'] ) : ?>
<h3>Horaires</h3>
<p><?php echo nl2br( esc_html( $vf_c['horaires'] ) ); ?></p>
<?php endif; ?>
<?php if ( $vf_c['pmr'] ) : ?>
<p class="contact__pmr" style="margin: 0"><?php echo vf_socle_icone( 'pmr', 20, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Accessible aux personnes à mobilité réduite</p>
<?php endif; ?>
</section>
