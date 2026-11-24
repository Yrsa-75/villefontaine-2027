<?php
/**
 * Thème Villefontaine : fonctions du thème.
 *
 * Le thème ne contient QUE l'apparence. Toute fonctionnalité métier
 * (méga-menu, démarches, actualités, agenda…) vit dans les plugins Villefontaine.
 *
 * @package Villefontaine
 */

defined( 'ABSPATH' ) || exit;

define( 'VF_THEME_VERSION', '1.0.14' );

/**
 * Réglages de base du thème.
 */
function vf_theme_reglages() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	// Les motifs proposés par WordPress.org ne correspondent pas à la charte : on les retire.
	remove_theme_support( 'core-block-patterns' );

	// Feuille de style chargée dans l'éditeur pour qu'il ressemble au site.
	add_editor_style( array( 'assets/css/global.css', 'assets/css/editeur.css' ) );

	// Tailles d'images utilisées par les gabarits (la conversion WebP est faite par WordPress).
	add_image_size( 'vf-carte', 720, 480, true );
	add_image_size( 'vf-bandeau', 1200, 800, true );
}
add_action( 'after_setup_theme', 'vf_theme_reglages' );

/**
 * WordPress génère les images en WebP quand le serveur le permet (plus léger que le JPEG).
 *
 * @param array $formats Correspondance type source => type de sortie.
 * @return array
 */
function vf_theme_formats_images( $formats ) {
	if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png']  = 'image/webp';
	}
	return $formats;
}
add_filter( 'image_editor_output_format', 'vf_theme_formats_images' );

/**
 * Feuilles de style et scripts du site public.
 */
function vf_theme_ressources() {
	wp_enqueue_style( 'vf-global', get_theme_file_uri( 'assets/css/global.css' ), array(), VF_THEME_VERSION );
	wp_enqueue_script(
		'vf-theme',
		get_theme_file_uri( 'assets/js/theme.js' ),
		array(),
		VF_THEME_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
// Accroché avant le rendu du gabarit : la feuille commune passe AVANT les feuilles des blocs,
// qui peuvent ainsi la compléter (même ordre que dans la maquette validée).
add_action( 'template_redirect', 'vf_theme_ressources', 1 );

/**
 * Dans <head> : marque la page « JavaScript actif » le plus tôt possible (évite un clignotement)
 * et précharge les deux polices principales pour accélérer l'affichage.
 */
function vf_theme_tete() {
	echo "<script>document.documentElement.classList.add('js');</script>\n";
	foreach ( array( 'figtree-latin-wght-normal', 'unbounded-latin-wght-normal' ) as $police ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( 'assets/fonts/' . $police . '.woff2' ) )
		);
	}
}
add_action( 'wp_head', 'vf_theme_tete', 1 );

/**
 * Classes ajoutées à <body> :
 * - « vf » sur tout le site ;
 * - « vf-immersif » quand la page commence par le grand visuel d'accueil (en-tête transparent).
 *
 * @param array $classes Classes existantes.
 * @return array
 */
function vf_theme_classes_body( $classes ) {
	$classes[] = 'vf';
	if ( is_singular() && has_block( 'vf/heros', get_queried_object_id() ) ) {
		$classes[] = 'vf-immersif';
	}
	return $classes;
}
add_filter( 'body_class', 'vf_theme_classes_body' );

/**
 * Logos du thème transmis aux blocs du plugin Socle (en-tête, pied de page).
 * Le plugin ne connaît pas le thème : il demande les logos via ce filtre.
 *
 * @return array
 */
function vf_theme_logos() {
	return array(
		'couleur' => get_theme_file_uri( 'assets/img/logo-villefontaine-couleur.png' ),
		'blanc'   => get_theme_file_uri( 'assets/img/logo-villefontaine-blanc.png' ),
		'largeur' => 300,
		'hauteur' => 102,
	);
}
add_filter( 'vf_socle_logos', 'vf_theme_logos' );

/**
 * Styles de blocs proposés aux agents (menu « Styles » du bloc dans l'éditeur).
 */
function vf_theme_styles_blocs() {
	register_block_style(
		'core/group',
		array(
			'name'  => 'a-savoir',
			'label' => 'Encadré « À savoir »',
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'encadre-bleu',
			'label' => 'Encadré bleu pâle',
		)
	);
	register_block_style(
		'core/list',
		array(
			'name'  => 'coches',
			'label' => 'Liste à coches',
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'secondaire',
			'label' => 'Bouton secondaire',
		)
	);
}
add_action( 'init', 'vf_theme_styles_blocs' );

/**
 * Catégories de modèles (motifs) dans l'éditeur.
 */
function vf_theme_categories_motifs() {
	register_block_pattern_category( 'villefontaine-pages', array( 'label' => 'Villefontaine : modèles de pages' ) );
	register_block_pattern_category( 'villefontaine-sections', array( 'label' => 'Villefontaine : sections' ) );
}
add_action( 'init', 'vf_theme_categories_motifs' );

/**
 * Allègement : retire du site public ce qui ne sert à rien (émojis WordPress, lien RSD, etc.).
 */
function vf_theme_allegement() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
}
add_action( 'init', 'vf_theme_allegement' );

// Le lien d'évitement automatique de WordPress ferait doublon avec ceux de l'en-tête (« Aller au contenu », « Aller au menu »).
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_block_template_skip_link' );
remove_action( 'wp_footer', 'the_block_template_skip_link' );

/**
 * Photo par défaut du grand visuel d'accueil (tant qu'aucune photo n'est choisie dans le bloc).
 *
 * @return array
 */
function vf_theme_image_heros() {
	$base = get_theme_file_uri( 'assets/img/hero-lac-' );
	return array(
		'src'     => $base . '1440.webp',
		'srcset'  => $base . '768.webp 768w, ' . $base . '1200.webp 1200w, ' . $base . '1440.webp 1440w',
		'largeur' => 1440,
		'hauteur' => 1116,
	);
}
add_filter( 'vf_socle_heros_image_defaut', 'vf_theme_image_heros' );

/**
 * Description de la page pour les moteurs de recherche (<meta name="description">) :
 * résumé de la page s'il existe, sinon début du texte ; slogan du site pour l'accueil et les listes.
 */
function vf_theme_meta_description() {
	$texte = '';
	if ( is_singular() && ! is_front_page() ) {
		$post  = get_queried_object();
		$texte = has_excerpt( $post ) ? $post->post_excerpt : wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( $post->post_content ) ) );
	} elseif ( is_post_type_archive() ) {
		$texte = (string) get_the_post_type_description();
	} elseif ( is_category() || is_tax() ) {
		$texte = term_description();
	}
	if ( ! trim( wp_strip_all_tags( (string) $texte ) ) ) {
		$texte = get_bloginfo( 'description' );
	}
	if ( ! trim( (string) $texte ) || 'Un site utilisant WordPress' === $texte ) {
		$texte = 'Site officiel de la Ville de Villefontaine (Isère) : vos démarches, l’actualité, l’agenda, les services et lieux de la ville.';
	}
	$texte = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $texte ) ) );
	if ( $texte ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_html_excerpt( $texte, 155, '…' ) ) );
	}
}
add_action( 'wp_head', 'vf_theme_meta_description', 2 );
