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

/**
 * Schéma interactif : remplace chaque sigle connu (DPC, PC…) par sa version expliquée.
 *
 * Le sigle devient un lien vers la démarche quand une adresse est renseignée ; au survol
 * ou au clavier, une bulle affiche sa signification. Les lecteurs d'écran lisent le sigle puis sa signification
 * (le nom accessible commence par le texte visible, pour la commande vocale).
 *
 * @param string $texte  Texte brut saisi par l'agent.
 * @param array  $sigles Sigles nettoyés : sigle => [ libelle, lienTexte, url ].
 * @return string HTML échappé.
 */
function vf_socle_schema_sigles( $texte, $sigles ) {
	$html = esc_html( $texte );
	if ( ! $sigles ) {
		return $html;
	}
	$codes = array_keys( $sigles );
	// Les sigles les plus longs d'abord : « DPC » ne doit pas être lu comme « PC ».
	usort(
		$codes,
		static function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}
	);
	$motif = '/(?<![\p{L}\p{N}])(' . implode( '|', array_map( 'preg_quote', $codes ) ) . ')(?![\p{L}\p{N}])/u';
	$html  = preg_replace_callback(
		$motif,
		static function ( $m ) use ( $sigles ) {
			return vf_socle_schema_sigle( $m[1], $sigles[ $m[1] ] );
		},
		$html
	);
	return null === $html ? esc_html( $texte ) : $html;
}

/**
 * Schéma interactif : HTML d'un sigle.
 *
 * @param string $code  Sigle.
 * @param array  $sigle [ libelle, lienTexte, url ].
 * @return string
 */
function vf_socle_schema_sigle( $code, $sigle ) {
	$bulle = '<span class="sigle__bulle" aria-hidden="true"><strong>' . esc_html( $sigle['libelle'] ) . '</strong>';
	if ( '' === $sigle['url'] ) {
		return '<span class="sigle" tabindex="0"><span class="sigle__code">' . esc_html( $code ) . '</span><span class="visuellement-masque"> (' . esc_html( $sigle['libelle'] ) . ')</span>' . $bulle . '</span></span>';
	}
	$action = '' !== $sigle['lienTexte'] ? $sigle['lienTexte'] : 'Voir la démarche';
	return '<a class="sigle" href="' . esc_url( $sigle['url'] ) . '"' . vf_socle_attributs_lien( $sigle['url'] ) . '><span class="sigle__code">' . esc_html( $code ) . '</span><span class="visuellement-masque"> (' . esc_html( $sigle['libelle'] ) . ') : ' . esc_html( $action ) . '</span>' . vf_socle_mention_lien( $sigle['url'] ) . $bulle . '<span>' . esc_html( $action ) . '</span></span></a>';
}

/**
 * Schéma interactif : transforme les règles saisies (une par ligne) en liste.
 *
 * Format d'une ligne : « condition : dossier » (ex. « ≤ 20 m² : DPC »), ou un texte seul.
 * Une ligne qui commence par « # » est un intertitre à l'intérieur de la fiche.
 *
 * @param string $regles Texte saisi.
 * @param array  $sigles Sigles nettoyés.
 * @return string HTML échappé.
 */
function vf_socle_schema_regles( $regles, $sigles ) {
	$lignes = array_filter( array_map( 'trim', preg_split( '/\R/u', (string) $regles ) ), 'strlen' );
	if ( ! $lignes ) {
		return '';
	}
	$html = '<ul class="schema__regles">';
	foreach ( $lignes as $ligne ) {
		if ( '#' === $ligne[0] ) {
			$html .= '<li class="regle regle--titre">' . vf_socle_schema_sigles( trim( substr( $ligne, 1 ) ), $sigles ) . '</li>';
			continue;
		}
		$condition = '';
		$resultat  = $ligne;
		// La dernière espace + deux-points sépare la condition du dossier à déposer.
		if ( preg_match( '/^(.+?)\s*:\s+([^:]+)$/u', $ligne, $m ) ) {
			$condition = $m[1];
			$resultat  = $m[2];
		}
		$libre = (bool) preg_match( '/^aucune\s+formalit/iu', $resultat );
		$res   = vf_socle_schema_sigles( $libre ? ucfirst( $resultat ) : $resultat, $sigles );
		// Quand le résultat est un sigle seul, sa signification est écrite en toutes lettres à côté.
		if ( isset( $sigles[ $resultat ] ) ) {
			$res .= ' <span class="regle__nom">' . esc_html( $sigles[ $resultat ]['libelle'] ) . '</span>';
		}
		$html .= '<li class="regle' . ( '' === $condition ? ' regle--seule' : '' ) . '">';
		if ( '' !== $condition ) {
			$html .= '<span class="regle__cond">' . vf_socle_schema_sigles( $condition, $sigles ) . '</span> ';
		}
		$html .= '<span class="regle__res' . ( $libre ? ' regle__res--libre' : '' ) . '">' . $res . '</span></li>';
	}
	return $html . '</ul>';
}
