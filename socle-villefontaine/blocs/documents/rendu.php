<?php
/**
 * Bloc « Documents à télécharger ». Le type et le poids sont calculés automatiquement
 * pour les fichiers de la médiathèque (RGAA 13.3 : informer du format et du poids).
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_docs = array_filter(
	(array) $attributes['documents'],
	static function ( $d ) {
		return ! empty( $d['url'] );
	}
);
if ( ! $vf_docs ) {
	return;
}
?>
<div class="vf-documents">
<?php if ( $attributes['titre'] ) : ?><h2><?php echo esc_html( $attributes['titre'] ); ?></h2><?php endif; ?>
<ul class="documents">
	<?php
	foreach ( $vf_docs as $vf_d ) :
		$vf_url  = $vf_d['url'];
		$vf_ext  = strtoupper( (string) pathinfo( (string) wp_parse_url( $vf_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		$vf_info = $vf_ext ? $vf_ext : 'Lien';
		$vf_id   = attachment_url_to_postid( $vf_url );
		if ( $vf_id ) {
			$vf_fichier = get_attached_file( $vf_id );
			if ( $vf_fichier && file_exists( $vf_fichier ) ) {
				$vf_info .= ', ' . str_replace( '.', ',', size_format( filesize( $vf_fichier ), 1 ) );
			}
		}
		$vf_titre = ! empty( $vf_d['titre'] ) ? $vf_d['titre'] : wp_basename( $vf_url );
		?>
	<li><a href="<?php echo esc_url( $vf_url ); ?>"<?php echo vf_socle_attributs_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo vf_socle_icone( 'doc', 22, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="documents__titre"><?php echo esc_html( $vf_titre ); ?><span class="visuellement-masque"> (<?php echo esc_html( $vf_info ); ?>)</span><?php echo vf_socle_mention_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="documents__info" aria-hidden="true"><?php echo esc_html( $vf_info ); ?></span></a></li>
	<?php endforeach; ?>
</ul>
</div>
