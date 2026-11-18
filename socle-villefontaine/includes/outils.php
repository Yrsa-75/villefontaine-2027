<?php
/**
 * Outils communs : liens sortants, logos, ancres des titres.
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Indique si une adresse mène hors du site.
 *
 * @param string $url Adresse à tester.
 * @return bool
 */
function vf_socle_est_externe( $url ) {
	$hote = wp_parse_url( (string) $url, PHP_URL_HOST );
	if ( ! $hote ) {
		return false; // Lien relatif, ancre, tel:, mailto: : reste sur le site.
	}
	$hote_site = wp_parse_url( home_url(), PHP_URL_HOST );
	return strtolower( $hote ) !== strtolower( (string) $hote_site );
}

/**
 * Attributs à ajouter à un lien : nouvel onglet pour les liens sortants (demande du client).
 *
 * @param string $url Adresse du lien.
 * @return string Attributs HTML déjà échappés (ou chaîne vide).
 */
function vf_socle_attributs_lien( $url ) {
	return vf_socle_est_externe( $url ) ? ' target="_blank" rel="noopener"' : '';
}

/**
 * Mention invisible lue par les lecteurs d'écran pour un lien qui ouvre un nouvel onglet (RGAA 13.2).
 *
 * @param string $url Adresse du lien.
 * @return string
 */
function vf_socle_mention_lien( $url ) {
	return vf_socle_est_externe( $url ) ? '<span class="visuellement-masque"> (nouvelle fenêtre)</span>' : '';
}

/**
 * Logos fournis par le thème (filtre « vf_socle_logos »). Le plugin ne dépend pas du thème.
 *
 * @return array{couleur:string,blanc:string,largeur:int,hauteur:int}
 */
function vf_socle_logos() {
	$logos = apply_filters(
		'vf_socle_logos',
		array(
			'couleur' => '',
			'blanc'   => '',
			'largeur' => 200,
			'hauteur' => 68,
		)
	);
	// Le logo choisi dans « Identité du site » prend le dessus sur le logo couleur du thème.
	$id_logo = (int) get_theme_mod( 'custom_logo' );
	if ( $id_logo ) {
		$image = wp_get_attachment_image_src( $id_logo, 'full' );
		if ( $image ) {
			$logos['couleur'] = $image[0];
		}
	}
	return $logos;
}

/* ---------------------------------------------------------------------------
   Liens sortants saisis par les agents dans les contenus : ouverture dans un
   nouvel onglet + information pour les lecteurs d'écran (aria-describedby).
   --------------------------------------------------------------------------- */

/**
 * Ajoute target="_blank", rel="noopener" et la mention « nouvelle fenêtre » aux liens sortants d'un bloc.
 *
 * @param string $html  HTML du bloc.
 * @param array  $bloc  Bloc analysé.
 * @return string
 */
function vf_socle_liens_sortants_contenu( $html, $bloc ) {
	if ( is_admin() || false === strpos( $html, 'href=' ) || empty( $bloc['blockName'] ) ) {
		return $html;
	}
	// Les blocs Villefontaine gèrent déjà leurs liens eux-mêmes.
	if ( 0 === strpos( $bloc['blockName'], 'vf/' ) ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	while ( $p->next_tag( 'a' ) ) {
		$href = $p->get_attribute( 'href' );
		if ( ! is_string( $href ) || ! vf_socle_est_externe( $href ) ) {
			continue;
		}
		if ( null === $p->get_attribute( 'target' ) ) {
			$p->set_attribute( 'target', '_blank' );
		}
		$rel = (string) $p->get_attribute( 'rel' );
		if ( false === strpos( $rel, 'noopener' ) ) {
			$p->set_attribute( 'rel', trim( $rel . ' noopener' ) );
		}
		if ( null === $p->get_attribute( 'aria-describedby' ) ) {
			$p->set_attribute( 'aria-describedby', 'vf-nouvelle-fenetre' );
		}
	}
	return $p->get_updated_html();
}
add_filter( 'render_block', 'vf_socle_liens_sortants_contenu', 20, 2 );

/**
 * Texte de référence (invisible) utilisé par aria-describedby ci-dessus.
 */
function vf_socle_texte_nouvelle_fenetre() {
	echo '<span id="vf-nouvelle-fenetre" hidden>Ouvre une nouvelle fenêtre</span>' . "\n";
}
add_action( 'wp_footer', 'vf_socle_texte_nouvelle_fenetre' );

/* ---------------------------------------------------------------------------
   Ancres des intertitres : chaque titre de niveau 2 du contenu reçoit un
   identifiant, utilisé par le bloc « Sommaire de la page ».
   --------------------------------------------------------------------------- */

/**
 * Calcule l'identifiant d'un intertitre, en évitant les doublons sur la page.
 *
 * @param string $texte  Texte du titre.
 * @param array  $deja   Identifiants déjà utilisés (modifié par référence).
 * @return string
 */
function vf_socle_ancre( $texte, array &$deja ) {
	$base = sanitize_title( wp_strip_all_tags( $texte ) );
	if ( '' === $base ) {
		$base = 'section';
	}
	$id = $base;
	$n  = 2;
	while ( in_array( $id, $deja, true ) ) {
		$id = $base . '-' . $n++;
	}
	$deja[] = $id;
	return $id;
}

/**
 * Liste les intertitres de niveau 2 d'un contenu (pour le sommaire), dans l'ordre.
 *
 * @param int $post_id Identifiant du contenu.
 * @return array<int,array{id:string,texte:string}>
 */
function vf_socle_intertitres( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || '' === $post->post_content ) {
		return array();
	}
	$titres = array();
	$deja   = array();
	$visite = function ( $blocs ) use ( &$visite, &$titres, &$deja ) {
		foreach ( $blocs as $bloc ) {
			if ( 'core/heading' === $bloc['blockName'] && 2 === (int) ( $bloc['attrs']['level'] ?? 2 ) ) {
				$texte = trim( wp_strip_all_tags( $bloc['innerHTML'] ) );
				if ( '' !== $texte ) {
					$id       = ! empty( $bloc['attrs']['anchor'] ) ? sanitize_html_class( $bloc['attrs']['anchor'] ) : vf_socle_ancre( $texte, $deja );
					$titres[] = array(
						'id'    => $id,
						'texte' => $texte,
					);
				}
			}
			if ( ! empty( $bloc['innerBlocks'] ) ) {
				$visite( $bloc['innerBlocks'] );
			}
		}
	};
	$visite( parse_blocks( $post->post_content ) );
	return $titres;
}

/**
 * Pendant l'affichage du contenu principal, ajoute l'identifiant aux titres de niveau 2 qui n'en ont pas.
 *
 * @param string $html HTML du bloc titre.
 * @param array  $bloc Bloc analysé.
 * @return string
 */
function vf_socle_ancre_titres( $html, $bloc ) {
	static $deja = array();
	static $post = 0;
	if ( empty( $GLOBALS['vf_socle_dans_contenu'] ) || 'core/heading' !== $bloc['blockName'] || 2 !== (int) ( $bloc['attrs']['level'] ?? 2 ) ) {
		return $html;
	}
	if ( get_the_ID() !== $post ) {
		$post = get_the_ID();
		$deja = array();
	}
	$texte = trim( wp_strip_all_tags( $html ) );
	if ( '' === $texte || ! empty( $bloc['attrs']['anchor'] ) ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	if ( $p->next_tag( 'h2' ) && null === $p->get_attribute( 'id' ) ) {
		$p->set_attribute( 'id', vf_socle_ancre( $texte, $deja ) );
	}
	return $p->get_updated_html();
}
add_filter( 'render_block_core/heading', 'vf_socle_ancre_titres', 10, 2 );

add_filter(
	'the_content',
	function ( $contenu ) {
		$GLOBALS['vf_socle_dans_contenu'] = true;
		return $contenu;
	},
	8
);
add_filter(
	'the_content',
	function ( $contenu ) {
		$GLOBALS['vf_socle_dans_contenu'] = false;
		return $contenu;
	},
	10
);
