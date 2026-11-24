<?php
/**
 * Bloc « Schéma interactif ».
 *
 * Une illustration porte des points numérotés. Chaque point est un bouton qui ouvre une fiche
 * (règles, sigles expliqués, lien vers la démarche). Sous l'image, la liste complète des fiches
 * reprend les mêmes numéros : tout le contenu reste lisible sans JavaScript, au clavier,
 * avec un lecteur d'écran et à l'impression (RGAA).
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

// Sigles : sigle => [ libelle, lienTexte, url ].
$vf_sigles = array();
foreach ( (array) $attributes['sigles'] as $vf_s ) {
	$vf_code = trim( (string) ( $vf_s['sigle'] ?? '' ) );
	$vf_lib  = trim( (string) ( $vf_s['libelle'] ?? '' ) );
	if ( '' !== $vf_code && '' !== $vf_lib ) {
		$vf_sigles[ $vf_code ] = array(
			'libelle'   => $vf_lib,
			'lienTexte' => trim( (string) ( $vf_s['lienTexte'] ?? '' ) ),
			'url'       => trim( (string) ( $vf_s['url'] ?? '' ) ),
		);
	}
}

// Points : seuls ceux qui ont un titre sont affichés.
$vf_points = array();
foreach ( (array) $attributes['points'] as $vf_p ) {
	$vf_titre = trim( (string) ( $vf_p['titre'] ?? '' ) );
	if ( '' === $vf_titre ) {
		continue;
	}
	$vf_points[] = array(
		'titre'     => $vf_titre,
		'x'         => max( 0, min( 100, (float) str_replace( ',', '.', (string) ( $vf_p['x'] ?? 50 ) ) ) ),
		'y'         => max( 0, min( 100, (float) str_replace( ',', '.', (string) ( $vf_p['y'] ?? 50 ) ) ) ),
		'regles'    => vf_socle_schema_regles( $vf_p['regles'] ?? '', $vf_sigles ),
		'lienTexte' => trim( (string) ( $vf_p['lienTexte'] ?? '' ) ),
		'lienUrl'   => trim( (string) ( $vf_p['lienUrl'] ?? '' ) ),
	);
}
if ( ! $vf_points ) {
	return;
}

$vf_id    = wp_unique_id( 'schema-' );
$vf_h     = 2 === (int) $attributes['niveau'] ? 'h2' : 'h3';
$vf_image = vf_socle_image(
	(int) $attributes['imageId'],
	'large',
	array(
		'alt'      => trim( (string) $attributes['imageAlt'] ),
		'loading'  => 'lazy',
		'decoding' => 'async',
		'sizes'    => '(max-width: 980px) 100vw, 800px',
	)
);

/**
 * Lien propre à un point (ex. formulaire particulier).
 */
$vf_lien = static function ( $p ) {
	if ( '' === $p['lienUrl'] || '' === $p['lienTexte'] ) {
		return '';
	}
	return '<p class="schema__lien"><a href="' . esc_url( $p['lienUrl'] ) . '"' . vf_socle_attributs_lien( $p['lienUrl'] ) . '>' . esc_html( $p['lienTexte'] ) . vf_socle_mention_lien( $p['lienUrl'] ) . '</a></p>';
};

/**
 * Position d'un point sur l'image, transmise au CSS (en % depuis la gauche et le haut).
 */
$vf_position = static function ( $p ) {
	return sprintf( '--x: %s%%; --y: %s%%', number_format( $p['x'], 1, '.', '' ), number_format( $p['y'], 1, '.', '' ) );
};

$vf_consigne = trim( (string) $attributes['consigne'] );
$vf_rappel   = trim( (string) $attributes['rappel'] );
$vf_titre_l  = trim( (string) $attributes['titreListe'] );
?>
<div class="schema" data-schema>
<?php if ( '' !== $vf_image ) : ?>
	<?php if ( '' !== $vf_consigne ) : ?>
	<p class="schema__consigne"><?php echo esc_html( $vf_consigne ); ?></p>
	<?php endif; ?>
	<div class="schema__image">
		<?php // La scène (image + points) et les fiches sont séparées : sur téléphone, la fiche ouverte s'affiche sous l'image sans décaler les points. ?>
		<div class="schema__scene">
			<?php echo $vf_image; // phpcs:ignore WordPress.Security.EscapeOutput -- produit par wp_get_attachment_image. ?>
			<div class="schema__points" role="group" aria-label="Points du schéma">
			<?php foreach ( $vf_points as $vf_i => $vf_p ) : ?>
				<button type="button" class="schema__point" style="<?php echo esc_attr( $vf_position( $vf_p ) ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $vf_id . '-bulle-' . ( $vf_i + 1 ) ); ?>" data-point="<?php echo (int) ( $vf_i + 1 ); ?>"><span aria-hidden="true"><?php echo (int) ( $vf_i + 1 ); ?></span><span class="visuellement-masque"><?php echo esc_html( $vf_p['titre'] ); ?></span></button>
			<?php endforeach; ?>
			</div>
		</div>
		<div class="schema__bulles">
		<?php foreach ( $vf_points as $vf_i => $vf_p ) : ?>
			<?php
			$vf_n      = $vf_i + 1;
			$vf_classe = 'schema__bulle' . ( $vf_p['x'] > 52 ? ' est-a-gauche' : '' ) . ( $vf_p['y'] > 50 ? ' est-en-haut' : '' );
			?>
			<div class="<?php echo esc_attr( $vf_classe ); ?>" id="<?php echo esc_attr( $vf_id . '-bulle-' . $vf_n ); ?>" style="<?php echo esc_attr( $vf_position( $vf_p ) ); ?>" role="group" aria-labelledby="<?php echo esc_attr( $vf_id . '-titre-' . $vf_n ); ?>" tabindex="-1" hidden>
				<p class="schema__bulle-titre" id="<?php echo esc_attr( $vf_id . '-titre-' . $vf_n ); ?>"><span class="schema__numero" aria-hidden="true"><?php echo (int) $vf_n; ?></span><?php echo esc_html( $vf_p['titre'] ); ?></p>
				<?php echo $vf_p['regles']; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans vf_socle_schema_regles(). ?>
				<?php echo $vf_lien( $vf_p ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé lors de la construction. ?>
				<button type="button" class="schema__fermer" data-fermer><?php echo vf_socle_icone( 'croix', 16, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="visuellement-masque">Fermer la fiche « <?php echo esc_html( $vf_p['titre'] ); ?> »</span></button>
			</div>
		<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
<?php if ( '' !== $vf_rappel ) : ?>
	<p class="schema__rappel"><?php echo vf_socle_schema_sigles( $vf_rappel, $vf_sigles ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction. ?></p>
<?php endif; ?>
<?php if ( $vf_sigles ) : ?>
	<dl class="schema__sigles">
	<?php foreach ( $vf_sigles as $vf_code => $vf_s ) : ?>
		<div>
			<dt><?php echo esc_html( $vf_code ); ?></dt>
			<dd><?php echo esc_html( $vf_s['libelle'] ); ?><?php if ( '' !== $vf_s['url'] ) : ?> <a href="<?php echo esc_url( $vf_s['url'] ); ?>"<?php echo vf_socle_attributs_lien( $vf_s['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( '' !== $vf_s['lienTexte'] ? $vf_s['lienTexte'] : 'Voir la démarche' ); ?><span class="visuellement-masque"> : <?php echo esc_html( $vf_s['libelle'] ); ?></span><?php echo vf_socle_mention_lien( $vf_s['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a><?php endif; ?></dd>
		</div>
	<?php endforeach; ?>
	</dl>
<?php endif; ?>
<?php if ( '' !== $vf_titre_l ) : ?>
	<<?php echo $vf_h; // phpcs:ignore WordPress.Security.EscapeOutput -- h2 ou h3. ?> class="schema__titre-liste"><?php echo esc_html( $vf_titre_l ); ?></<?php echo $vf_h; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
<?php endif; ?>
	<ol class="schema__fiches">
	<?php foreach ( $vf_points as $vf_i => $vf_p ) : ?>
		<li class="schema__fiche" data-fiche="<?php echo (int) ( $vf_i + 1 ); ?>">
			<p class="schema__fiche-titre"><span class="schema__numero" aria-hidden="true"><?php echo (int) ( $vf_i + 1 ); ?></span><?php echo esc_html( $vf_p['titre'] ); ?></p>
			<?php echo $vf_p['regles']; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans vf_socle_schema_regles(). ?>
			<?php echo $vf_lien( $vf_p ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé lors de la construction. ?>
		</li>
	<?php endforeach; ?>
	</ol>
</div>
