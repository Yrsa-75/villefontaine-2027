<?php
/**
 * Bloc « Carte des services ».
 *
 * Données des lieux transmises en JSON dans un attribut (échappé) : aucune requête supplémentaire.
 * La carte (Leaflet, hébergée sur le site) et le fond Plan IGN (service public, sans cookie ni traceur)
 * s'affichent sans clic, conformément au choix validé.
 *
 * @package AnnuaireVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_d = vf_annuaire_donnees( (array) $attributes['categories'] );
if ( ! $vf_d['points'] ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		echo '<p class="vf-vide">Carte des services : ajoutez des lieux avec leurs coordonnées dans « Annuaire ».</p>';
	}
	return;
}
$vf_apercu = defined( 'REST_REQUEST' ) && REST_REQUEST;
if ( ! $vf_apercu ) {
	wp_enqueue_style( 'vf-leaflet' );
}
$vf_id    = wp_unique_id( 'carte-' );
$vf_titre = trim( (string) $attributes['titre'] );
$vf_url   = trim( (string) $attributes['lienUrl'] );
$vf_plein = 'pleine' === $attributes['format'];
?>
<section class="conteneur section vf-carte-bloc<?php echo $vf_plein ? ' vf-carte-bloc--pleine' : ''; ?>"<?php echo $vf_titre ? ' aria-labelledby="' . esc_attr( $vf_id ) . '-titre"' : ''; ?>>
<div class="carte-services apparition">
<div class="carte-services__texte">
<?php if ( $attributes['surtitre'] ) : ?><p class="section__surtitre"><?php echo esc_html( $attributes['surtitre'] ); ?></p><?php endif; ?>
<?php if ( $vf_titre ) : ?><h2 id="<?php echo esc_attr( $vf_id ); ?>-titre"><?php echo esc_html( $vf_titre ); ?></h2><?php endif; ?>
<?php if ( $attributes['texte'] ) : ?><p><?php echo esc_html( $attributes['texte'] ); ?></p><?php endif; ?>
<ul class="categories" aria-label="Afficher ou masquer une catégorie">
	<?php foreach ( $vf_d['categories'] as $vf_nom => $vf_coul ) : ?>
	<li><button type="button" class="filtre-carte" data-categorie="<?php echo esc_attr( $vf_nom ); ?>" aria-pressed="true"><i style="background: <?php echo esc_attr( $vf_coul ); ?>"></i><?php echo esc_html( $vf_nom ); ?></button></li>
	<?php endforeach; ?>
</ul>
<details class="liste-lieux"<?php echo $vf_plein ? ' open' : ''; ?>><summary>Voir la liste des lieux (<span data-compte><?php echo (int) count( $vf_d['points'] ); ?></span>)</summary><ul>
	<?php foreach ( $vf_d['points'] as $vf_p ) : ?>
	<li data-categorie="<?php echo esc_attr( $vf_p['categorie'] ); ?>"><button type="button" data-point="<?php echo esc_attr( $vf_p['id'] ); ?>"><i style="background: <?php echo esc_attr( $vf_d['categories'][ $vf_p['categorie'] ] ); ?>"></i><span><strong><?php echo esc_html( $vf_p['nom'] ); ?></strong><small><?php echo esc_html( $vf_p['categorie'] . ( $vf_p['adresse'] ? ' · ' . $vf_p['adresse'] : '' ) ); ?></small></span></button></li>
	<?php endforeach; ?>
</ul></details>
<?php if ( $attributes['lienTexte'] && $vf_url ) : ?>
<p style="margin: 0"><a class="bouton bouton--clair" href="<?php echo esc_url( $vf_url ); ?>"><?php echo esc_html( $attributes['lienTexte'] ); ?> <?php echo vf_socle_icone( 'fleche', 18, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></p>
<?php endif; ?>
</div>
<div class="carte-services__visuel">
	<div id="<?php echo esc_attr( $vf_id ); ?>" class="carte-ville" role="region" aria-label="Carte interactive des services de la ville (la liste des lieux ci-contre donne les mêmes informations)" data-points="<?php echo esc_attr( wp_json_encode( $vf_d['points'] ) ); ?>" data-couleurs="<?php echo esc_attr( wp_json_encode( $vf_d['categories'] ) ); ?>">
		<?php if ( $vf_apercu ) : ?><p style="padding: 24px">La carte interactive s’affiche sur le site.</p><?php endif; ?>
	</div>
	<span class="carte-services__mention">Fond de carte : Plan IGN</span>
</div>
</div>
</section>
