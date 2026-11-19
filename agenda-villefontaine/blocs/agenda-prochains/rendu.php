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

// Format « liste » : lignes compactes, pour les pages thématiques (ex. « Prochains rendez-vous » de la page Seniors).
if ( 'liste' === ( $attributes['format'] ?? 'cartes' ) ) {
	$vf_occs = array_slice( $vf_occs, 0, $vf_nombre );
	echo '<section class="vf-agenda-liste-compacte">';
	if ( ! empty( $attributes['titre'] ) ) {
		echo '<h2>' . esc_html( $attributes['titre'] ) . '</h2>';
	}
	echo '<ul class="liste-evenements">';
	foreach ( $vf_occs as $vf_occ ) {
		$vf_p    = $vf_occ['post'];
		$vf_jour = strtotime( max( $vf_occ['debut'], vf_agenda_aujourdhui() ) . ' 12:00' );
		$vf_lieu = (string) get_post_meta( $vf_p->ID, 'vf_lieu', true );
		echo '<li class="ligne-evt apparition">';
		echo get_the_post_thumbnail( $vf_p, 'medium', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<div><h3><a href="' . esc_url( get_permalink( $vf_p ) ) . '">' . esc_html( get_the_title( $vf_p ) ) . '</a></h3>';
		echo '<p>' . esc_html( vf_agenda_quand( $vf_p->ID, $vf_occ ) . ( $vf_lieu ? ' · ' . $vf_lieu : '' ) ) . '</p></div>';
		echo '<p class="ligne-evt__date" aria-hidden="true"><strong>' . esc_html( gmdate( 'j', $vf_jour ) ) . '</strong><span>' . esc_html( vf_agenda_noms( 'mois' )[ (int) gmdate( 'n', $vf_jour ) ] ) . '</span></p>';
		echo '</li>';
	}
	$vf_lien_tout = $vf_theme ? get_term_link( $vf_theme, 'vf_theme_evenement' ) : $vf_archive;
	$vf_lien_tout = is_wp_error( $vf_lien_tout ) ? $vf_archive : $vf_lien_tout;
	echo '</ul><p class="vf-agenda-liste-compacte__lien"><a class="lien-fleche" href="' . esc_url( $vf_lien_tout ) . '"><span>Tout l’agenda</span> ' . vf_socle_icone( 'fleche', 16, 2.2 ) . '</a></p></section>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
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
