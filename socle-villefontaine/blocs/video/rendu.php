<?php
/**
 * Bloc « Vidéo (avec consentement) ». Rien n'est chargé depuis YouTube ou Vimeo
 * tant que le visiteur n'a pas cliqué sur « Lire la vidéo » (RGPD).
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_url = (string) $attributes['url'];
$vf_src = '';
$vf_srv = '';
if ( preg_match( '#(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,15})#', $vf_url, $vf_m ) ) {
	$vf_src = 'https://www.youtube-nocookie.com/embed/' . $vf_m[1] . '?autoplay=1&rel=0';
	$vf_srv = 'YouTube';
} elseif ( preg_match( '#vimeo\.com/(?:video/)?(\d+)#', $vf_url, $vf_m ) ) {
	$vf_src = 'https://player.vimeo.com/video/' . $vf_m[1] . '?autoplay=1&dnt=1';
	$vf_srv = 'Vimeo';
}
if ( ! $vf_src ) {
	return;
}
// Titre explicite (RGAA 2.2) ; à défaut, on précise au moins la page concernée.
$vf_titre = $attributes['titre'] ? $attributes['titre'] : sprintf( 'Vidéo %1$s : %2$s', $vf_srv, get_the_title() );
?>
<figure class="vf-video" data-src="<?php echo esc_url( $vf_src ); ?>" data-titre="<?php echo esc_attr( $vf_titre ); ?>">
	<div class="vf-video__accord">
		<span class="vf-video__lecture" aria-hidden="true"><svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
		<p><strong><?php echo esc_html( $vf_titre ); ?></strong></p>
		<p>Cette vidéo est hébergée par <?php echo esc_html( $vf_srv ); ?>, qui peut déposer des cookies. Elle ne se charge qu’après votre accord.</p>
		<p><button type="button" class="bouton">Lire la vidéo<span class="visuellement-masque"> : <?php echo esc_html( $vf_titre ); ?></span></button> <a href="<?php echo esc_url( $vf_url ); ?>" target="_blank" rel="noopener">Voir sur <?php echo esc_html( $vf_srv ); ?><span class="visuellement-masque"> (nouvelle fenêtre)</span></a></p>
	</div>
</figure>
<?php
// Transcription textuelle (RGAA 4.1) : texte intégral de la vidéo, ouvert à la demande.
$vf_transcription = trim( (string) ( $attributes['transcription'] ?? '' ) );
if ( '' !== $vf_transcription ) :
	?>
<details class="vf-video__transcription">
	<summary>Transcription textuelle<span class="visuellement-masque"> : <?php echo esc_html( $vf_titre ); ?></span></summary>
	<div><?php echo wp_kses_post( wpautop( esc_html( $vf_transcription ) ) ); ?></div>
</details>
<?php endif; ?>
