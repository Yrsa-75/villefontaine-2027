<?php
/**
 * Bloc « Plan du site » : toutes les pages publiées (arborescence) et les grandes rubriques dynamiques.
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_exclues = array_filter( array( (int) get_option( 'page_on_front' ), (int) get_option( 'page_for_posts' ) ) );
$vf_pages   = wp_list_pages(
	array(
		'title_li'    => '',
		'echo'        => false,
		'sort_column' => 'menu_order,post_title',
		'exclude'     => implode( ',', $vf_exclues ),
	)
);
$vf_extras = array( array( 'Accueil', home_url( '/' ) ) );
if ( get_option( 'page_for_posts' ) ) {
	$vf_extras[] = array( 'Actualités', get_permalink( (int) get_option( 'page_for_posts' ) ) );
}
foreach ( array( 'vf_evenement' => 'Agenda', 'vf_publication' => 'Kiosque' ) as $vf_type => $vf_lib ) {
	if ( post_type_exists( $vf_type ) && get_post_type_archive_link( $vf_type ) ) {
		$vf_extras[] = array( $vf_lib, get_post_type_archive_link( $vf_type ) );
	}
}
?>
<nav class="vf-plan" aria-label="Plan du site">
	<ul class="vf-plan__essentiel">
		<?php foreach ( $vf_extras as $vf_e ) : ?>
			<li><a href="<?php echo esc_url( $vf_e[1] ); ?>"><?php echo esc_html( $vf_e[0] ); ?></a></li>
		<?php endforeach; ?>
	</ul>
	<ul class="vf-plan__pages"><?php echo $vf_pages; // phpcs:ignore WordPress.Security.EscapeOutput -- produit et échappé par WordPress. ?></ul>
</nav>
