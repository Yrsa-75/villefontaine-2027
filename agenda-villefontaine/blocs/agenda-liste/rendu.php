<?php
/**
 * Bloc « Agenda complet » : événements des 12 prochains mois, par mois.
 *
 * @package AgendaVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_occs = vf_agenda_prochaines( 300, 365 );
if ( ! $vf_occs ) {
	echo '<p class="vf-vide">Aucun événement n’est programmé pour le moment. Revenez bientôt !</p>';
	return;
}
$vf_par_mois = array();
foreach ( $vf_occs as $vf_occ ) {
	$vf_par_mois[ substr( max( $vf_occ['debut'], vf_agenda_aujourdhui() ), 0, 7 ) ][] = $vf_occ;
}
?>
<div class="vf-agenda vf-agenda--liste" data-max="999">
<?php echo vf_agenda_filtres( $vf_occs ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction. ?>
<p class="visuellement-masque" aria-live="polite" data-annonce></p>
<?php
foreach ( $vf_par_mois as $vf_mois => $vf_liste ) :
	$vf_t   = strtotime( $vf_mois . '-01 12:00' );
	$vf_nom = ucfirst( vf_agenda_noms( 'mois' )[ (int) gmdate( 'n', $vf_t ) ] ) . ' ' . gmdate( 'Y', $vf_t );
	?>
<section class="agenda-mois" data-mois="<?php echo esc_attr( $vf_mois ); ?>" aria-labelledby="mois-<?php echo esc_attr( $vf_mois ); ?>">
<h2 id="mois-<?php echo esc_attr( $vf_mois ); ?>"><?php echo esc_html( $vf_nom ); ?></h2>
<ul class="agenda">
	<?php
	foreach ( $vf_liste as $vf_i => $vf_occ ) {
		echo vf_agenda_carte( $vf_occ, $vf_i ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction.
	}
	?>
</ul>
</section>
<?php endforeach; ?>
<p class="agenda__vide vf-vide" hidden>Aucun événement pour cette thématique dans les 12 prochains mois.</p>
</div>
