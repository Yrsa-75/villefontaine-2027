<?php
/**
 * Bloc « Grand visuel d'accueil ».
 *
 * Performance : la photo est l'élément le plus gros de la page (LCP) ; elle est donc chargée
 * en priorité (fetchpriority="high"), jamais en différé, et en plusieurs tailles (srcset).
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_img_attrs = array(
	'class'         => 'immersif__photo',
	'alt'           => '',
	'loading'       => 'eager',
	'fetchpriority' => 'high',
	'decoding'      => 'async',
	'sizes'         => '100vw',
);
$vf_image = vf_socle_image( (int) $attributes['imageId'], 'full', $vf_img_attrs );
if ( '' === $vf_image ) {
	// Image par défaut fournie par le thème : [ 'src' => url, 'srcset' => '...', 'largeur' => int, 'hauteur' => int ].
	$vf_defaut = apply_filters( 'vf_socle_heros_image_defaut', array() );
	if ( ! empty( $vf_defaut['src'] ) ) {
		$vf_image = sprintf(
			'<img class="immersif__photo" src="%s" srcset="%s" sizes="100vw" width="%d" height="%d" alt="" fetchpriority="high" decoding="async">',
			esc_url( $vf_defaut['src'] ),
			esc_attr( $vf_defaut['srcset'] ?? '' ),
			(int) ( $vf_defaut['largeur'] ?? 1440 ),
			(int) ( $vf_defaut['hauteur'] ?? 1116 )
		);
	}
}

// Diaporama : les autres photos ne sont pas dans la page au chargement (la première reste l'élément le plus
// gros, chargé en priorité). Leur adresse est transmise au script, qui les charge une fois la page affichée.
$vf_suite = array();
foreach ( array_slice( array_unique( array_map( 'absint', (array) ( $attributes['imagesSuite'] ?? array() ) ) ), 0, 7 ) as $vf_id ) {
	if ( ! $vf_id || $vf_id === (int) $attributes['imageId'] || ! wp_attachment_is_image( $vf_id ) ) {
		continue;
	}
	$vf_src = wp_get_attachment_image_src( $vf_id, 'full' );
	if ( $vf_src ) {
		$vf_suite[] = array(
			'src'     => $vf_src[0],
			'srcset'  => (string) wp_get_attachment_image_srcset( $vf_id, 'full' ),
			'largeur' => (int) $vf_src[1],
			'hauteur' => (int) $vf_src[2],
		);
	}
}
$vf_duree = max( 3, min( 15, (int) ( $attributes['duree'] ?? 5 ) ) );

$vf_flash = trim( (string) $attributes['flashTexte'] );
$vf_url   = trim( (string) $attributes['flashUrl'] );
?>
<section class="immersif" aria-labelledby="titre-accueil"<?php echo ( $vf_suite && '' !== $vf_image ) ? ' data-diaporama="' . esc_attr( wp_json_encode( $vf_suite ) ) . '" data-duree="' . (int) $vf_duree . '"' : ''; ?>>
<div class="immersif__photos">
<?php echo $vf_image; // phpcs:ignore WordPress.Security.EscapeOutput -- produit par wp_get_attachment_image ou échappé ci-dessus. ?>
</div>
<div class="aurore" aria-hidden="true"><span></span><span></span><span></span></div>
<div class="grille-lumineuse" aria-hidden="true"></div>
<div class="grain" aria-hidden="true"></div>
<div class="conteneur immersif__contenu">
<div class="immersif__bloc heros__contenu">
<?php if ( '' !== $vf_flash ) : ?>
	<?php if ( '' !== $vf_url ) : ?>
	<a class="capsule" href="<?php echo esc_url( $vf_url ); ?>"<?php echo vf_socle_attributs_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><b>Info du jour</b> <?php echo esc_html( $vf_flash ); ?><?php echo vf_socle_mention_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	<?php else : ?>
	<p class="capsule"><b>Info du jour</b> <?php echo esc_html( $vf_flash ); ?></p>
	<?php endif; ?>
<?php endif; ?>
<?php if ( '' !== trim( $attributes['surtitre'] ) ) : ?>
<p class="immersif__surtitre"><?php echo esc_html( $attributes['surtitre'] ); ?></p>
<?php endif; ?>
<h1 id="titre-accueil"><?php echo esc_html( $attributes['titre'] ); ?><?php if ( '' !== trim( $attributes['titreAccent'] ) ) : ?> <span class="degrade"><?php echo esc_html( $attributes['titreAccent'] ); ?></span><?php endif; ?></h1>
<form class="recherche recherche--verre" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
<label class="visuellement-masque" for="recherche-accueil">Rechercher une démarche ou une information</label>
<input id="recherche-accueil" name="s" type="search" placeholder="Une démarche, un service, un lieu…">
<button type="submit"><?php echo vf_socle_icone( 'loupe', 20, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Rechercher</button>
</form>
<?php
$vf_sugg = array_filter(
	(array) $attributes['suggestions'],
	static function ( $s ) {
		return ! empty( $s['texte'] );
	}
);
if ( $vf_sugg ) :
	?>
<p class="heros__suggestions"><span>Fréquent :</span>
	<?php
	foreach ( $vf_sugg as $vf_s ) :
		$vf_href = ! empty( $vf_s['url'] ) ? $vf_s['url'] : '#';
		$vf_pan  = ( '#panneau-demarches' === $vf_href );
		?>
<a href="<?php echo $vf_pan ? '#panneau-demarches' : esc_url( $vf_href ); ?>"<?php echo $vf_pan ? ' data-recherche="' . esc_attr( $vf_s['texte'] ) . '"' : vf_socle_attributs_lien( $vf_href ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $vf_s['texte'] ); ?><?php echo $vf_pan ? '' : vf_socle_mention_lien( $vf_href ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	<?php endforeach; ?>
</p>
<?php endif; ?>
</div>
</div>
</section>
