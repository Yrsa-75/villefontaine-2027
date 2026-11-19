<?php
/**
 * Bloc « Fiche du lieu ».
 *
 * @package AnnuaireVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_id = ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
if ( ! $vf_id || 'vf_lieu' !== get_post_type( $vf_id ) ) {
	return;
}
$vf = array();
foreach ( array_keys( vf_annuaire_champs() ) as $vf_cle ) {
	$vf[ $vf_cle ] = (string) get_post_meta( $vf_id, $vf_cle, true );
}
$vf_tel  = preg_replace( '/[^0-9+]/', '', $vf['vf_tel'] );
$vf_tel  = ( $vf_tel && '0' === $vf_tel[0] ) ? '+33' . substr( $vf_tel, 1 ) : $vf_tel;
$vf_carte = '' !== $vf['vf_lat'] && '' !== $vf['vf_lng'] && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
if ( $vf_carte ) {
	$vf_termes = get_the_terms( $vf_id, 'vf_categorie_lieu' );
	$vf_terme  = ( $vf_termes && ! is_wp_error( $vf_termes ) ) ? $vf_termes[0] : null;
	$vf_cat    = $vf_terme ? $vf_terme->name : 'Lieu';
	$vf_coul   = $vf_terme ? (string) get_term_meta( $vf_terme->term_id, 'vf_couleur', true ) : '';
	$vf_point  = array(
		array(
			'id'        => 'lieu-' . $vf_id,
			'nom'       => get_the_title( $vf_id ),
			'categorie' => $vf_cat,
			'adresse'   => $vf['vf_adresse'],
			'ville'     => trim( $vf['vf_code_postal'] . ' ' . $vf['vf_ville'] ),
			'lat'       => (float) $vf['vf_lat'],
			'lng'       => (float) $vf['vf_lng'],
			'telephone' => $vf['vf_tel'],
			'horaires'  => '',
			'url'       => '',
		),
	);
}
?>
<section class="lieu-fiche" aria-label="Coordonnées">
<dl>
	<?php if ( $vf['vf_adresse'] ) : ?>
	<div><dt><?php echo vf_socle_icone( 'lieu', 22, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Adresse</dt><dd><?php echo esc_html( $vf['vf_adresse'] ); ?><br><?php echo esc_html( trim( $vf['vf_code_postal'] . ' ' . $vf['vf_ville'] ) ); ?></dd></div>
	<?php endif; ?>
	<?php if ( $vf['vf_tel'] ) : ?>
	<div><dt><?php echo vf_socle_icone( 'tel', 22, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Téléphone</dt><dd><a href="tel:<?php echo esc_attr( $vf_tel ); ?>"><?php echo esc_html( $vf['vf_tel'] ); ?></a></dd></div>
	<?php endif; ?>
	<?php if ( $vf['vf_courriel'] && is_email( $vf['vf_courriel'] ) ) : ?>
	<div><dt><?php echo vf_socle_icone( 'courriel', 22, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Courriel</dt><dd><a href="mailto:<?php echo esc_attr( antispambot( $vf['vf_courriel'] ) ); ?>"><?php echo esc_html( antispambot( $vf['vf_courriel'] ) ); ?></a></dd></div>
	<?php endif; ?>
	<?php if ( $vf['vf_site'] ) : ?>
	<div><dt><?php echo vf_socle_icone( 'fleche', 22, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Site internet</dt><dd><a href="<?php echo esc_url( $vf['vf_site'] ); ?>"<?php echo vf_socle_attributs_lien( $vf['vf_site'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $vf['vf_site'] ) ) ); ?><?php echo vf_socle_mention_lien( $vf['vf_site'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></dd></div>
	<?php endif; ?>
	<?php if ( $vf['vf_horaires'] ) : ?>
	<div><dt><?php echo vf_socle_icone( 'horloge', 22, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Horaires</dt><dd><?php echo nl2br( esc_html( $vf['vf_horaires'] ) ); ?></dd></div>
	<?php endif; ?>
	<?php if ( 'oui' === $vf['vf_pmr'] ) : ?>
	<div><dt><?php echo vf_socle_icone( 'pmr', 22, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Accessibilité</dt><dd>Accessible aux personnes à mobilité réduite</dd></div>
	<?php endif; ?>
</dl>
<?php if ( $vf_carte ) : ?>
<div class="lieu-fiche__carte"><div class="carte-ville" role="region" aria-label="Carte : position du lieu" data-points="<?php echo esc_attr( wp_json_encode( $vf_point ) ); ?>" data-couleurs="<?php echo esc_attr( wp_json_encode( array( $vf_cat => $vf_coul ? $vf_coul : '#246170' ) ) ); ?>"></div></div>
<?php endif; ?>
</section>
