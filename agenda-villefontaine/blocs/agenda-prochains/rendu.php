<?php
/**
 * Bloc « Prochains événements ».
 * Sont préparés jusqu'à 4 fois plus d'événements que nécessaire : le filtre par thématique
 * peut ainsi afficher le bon nombre d'événements sans recharger la page.
 *
 * @package AgendaVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_nombre = max( 1, min( 12, (int) $attributes['nombre'] ) );
$vf_theme  = sanitize_title( (string) $attributes['thematique'] );
$vf_occs   = vf_agenda_prochaines( $vf_theme ? $vf_nombre : $vf_nombre * 4, 365, $vf_theme, true );
if ( ! $vf_occs ) {
	return;
}
$vf_archive = get_post_type_archive_link( 'vf_evenement' );
?>
<div class="vf-agenda" data-max="<?php echo (int) $vf_nombre; ?>">
<?php echo $vf_theme ? '' : vf_agenda_filtres( $vf_occs ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction. ?>
<p class="visuellement-masque" aria-live="polite" data-annonce></p>
<ul class="agenda">
<?php
foreach ( $vf_occs as $vf_i => $vf_occ ) {
	$vf_carte = vf_agenda_carte( $vf_occ, $vf_i );
	// Au-delà du nombre demandé, les cartes sont masquées (elles servent seulement aux filtres).
	echo $vf_i < $vf_nombre ? $vf_carte : preg_replace( '/^<li /', '<li hidden ', $vf_carte ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction.
}
?>
</ul>
<p class="agenda__vide" hidden>Aucun événement pour cette thématique. <a href="<?php echo esc_url( $vf_archive ); ?>">Voir tout l’agenda</a></p>
</div>
