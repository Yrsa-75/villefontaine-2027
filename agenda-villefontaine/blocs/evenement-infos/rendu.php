<?php
/**
 * Bloc « Infos de l'événement ».
 *
 * @package AgendaVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_id = ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
if ( ! $vf_id || 'vf_evenement' !== get_post_type( $vf_id ) ) {
	return;
}
$vf_aujourd = vf_agenda_aujourdhui();
$vf_occs    = vf_agenda_occurrences( $vf_id, $vf_aujourd, gmdate( 'Y-m-d', strtotime( '+1 year' ) ) );
$vf_passe   = ! $vf_occs;
if ( $vf_passe ) {
	$vf_debut = (string) get_post_meta( $vf_id, 'vf_debut', true );
	if ( ! $vf_debut ) {
		return;
	}
	$vf_fin  = (string) get_post_meta( $vf_id, 'vf_fin', true );
	$vf_occs = array( array( 'debut' => $vf_debut, 'fin' => $vf_fin ? $vf_fin : $vf_debut ) );
}
$vf_occ   = $vf_occs[0];
$vf_quand = $vf_occ['fin'] !== $vf_occ['debut']
	? 'Du ' . vf_agenda_date_texte( $vf_occ['debut'] ) . ' au ' . vf_agenda_date_texte( $vf_occ['fin'] )
	: ucfirst( vf_agenda_noms( 'jours' )[ (int) gmdate( 'w', strtotime( $vf_occ['debut'] . ' 12:00' ) ) ] ) . ' ' . vf_agenda_date_texte( $vf_occ['debut'] );
$vf_hd    = vf_agenda_heure( get_post_meta( $vf_id, 'vf_heure_debut', true ) );
$vf_hf    = vf_agenda_heure( get_post_meta( $vf_id, 'vf_heure_fin', true ) );
$vf_lieu  = (string) get_post_meta( $vf_id, 'vf_lieu', true );
$vf_adr   = (string) get_post_meta( $vf_id, 'vf_adresse', true );
$vf_infos = (string) get_post_meta( $vf_id, 'vf_infos', true );
$vf_lien  = (string) get_post_meta( $vf_id, 'vf_lien', true );
?>
<div class="evenement-infos">
<?php if ( $vf_passe ) : ?>
<p class="evenement-infos__passe">Cet événement est terminé.</p>
<?php endif; ?>
<dl>
	<div><dt><?php echo vf_socle_icone( 'calendrier', 22, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Quand</dt><dd><?php echo esc_html( $vf_quand ); ?>
		<?php if ( $vf_hd ) : ?><br><?php echo esc_html( $vf_hf ? $vf_hd . ' à ' . $vf_hf : 'À partir de ' . $vf_hd ); ?><?php endif; ?></dd></div>
	<?php if ( $vf_lieu || $vf_adr ) : ?>
	<div><dt><?php echo vf_socle_icone( 'lieu', 22, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Où</dt><dd><?php echo esc_html( $vf_lieu ); ?><?php if ( $vf_adr ) : ?><br><?php echo esc_html( $vf_adr ); ?><?php endif; ?></dd></div>
	<?php endif; ?>
	<?php if ( $vf_infos ) : ?>
	<div><dt><?php echo vf_socle_icone( 'info', 22, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Infos pratiques</dt><dd><?php echo esc_html( $vf_infos ); ?></dd></div>
	<?php endif; ?>
</dl>
<?php if ( count( $vf_occs ) > 1 ) : ?>
<details class="evenement-infos__dates"><summary>Prochaines dates (<?php echo (int) count( $vf_occs ); ?>)</summary><ul>
	<?php foreach ( array_slice( $vf_occs, 0, 24 ) as $vf_o ) : ?>
	<li><?php echo esc_html( ucfirst( vf_agenda_noms( 'jours' )[ (int) gmdate( 'w', strtotime( $vf_o['debut'] . ' 12:00' ) ) ] ) . ' ' . vf_agenda_date_texte( $vf_o['debut'] ) ); ?></li>
	<?php endforeach; ?>
</ul></details>
<?php endif; ?>
<?php if ( $vf_lien && ! $vf_passe ) : ?>
<p style="margin: 18px 0 0"><a class="bouton" href="<?php echo esc_url( $vf_lien ); ?>"<?php echo vf_socle_attributs_lien( $vf_lien ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>S’inscrire ou réserver<?php echo vf_socle_mention_lien( $vf_lien ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></p>
<?php endif; ?>
</div>
