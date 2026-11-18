<?php
/**
 * Bloc « Dans la même rubrique » : la page parente et ses sous-pages (la page en cours est signalée).
 * Une page de premier niveau affiche ses propres sous-pages. Rien n'est affiché s'il n'y a rien à proposer.
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_id = vf_socle_post_du_bloc( $block );
if ( ! $vf_id || 'page' !== get_post_type( $vf_id ) ) {
	return;
}
$vf_parent = (int) wp_get_post_parent_id( $vf_id );
$vf_racine = $vf_parent ? $vf_parent : $vf_id;
$vf_pages  = get_pages(
	array(
		'parent'      => $vf_racine,
		'sort_column' => 'menu_order,post_title',
		'post_status' => 'publish',
	)
);
if ( count( $vf_pages ) < ( $vf_parent ? 2 : 1 ) ) {
	return; // Seule page de sa rubrique : rien d'utile à proposer.
}
$vf_titre = get_the_title( $vf_racine );
?>
<nav class="vf-rubrique" aria-labelledby="vf-rubrique-titre">
	<p class="vf-rubrique__surtitre">Dans la même rubrique</p>
	<h2 id="vf-rubrique-titre" class="vf-rubrique__titre">
		<?php if ( $vf_parent ) : ?>
			<a href="<?php echo esc_url( get_permalink( $vf_racine ) ); ?>"><?php echo esc_html( $vf_titre ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $vf_titre ); ?>
		<?php endif; ?>
	</h2>
	<ul role="list">
		<?php foreach ( $vf_pages as $vf_p ) : ?>
			<li>
				<?php if ( (int) $vf_p->ID === (int) $vf_id ) : ?>
					<a href="<?php echo esc_url( get_permalink( $vf_p ) ); ?>" aria-current="page"><?php echo esc_html( get_the_title( $vf_p ) ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( get_permalink( $vf_p ) ); ?>"><?php echo esc_html( get_the_title( $vf_p ) ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
