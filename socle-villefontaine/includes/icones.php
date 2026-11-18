<?php
/**
 * Jeu d'icônes du site (traits SVG, 24 × 24). Aucune police d'icônes externe.
 *
 * @package SocleVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Liste des icônes disponibles : nom => contenu SVG.
 *
 * @return array
 */
function vf_socle_icones() {
	return array(
		'chevron' => '<path d="m6 9 6 6 6-6"/>',
		'fleche' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'loupe' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'croix' => '<path d="M18 6 6 18M6 6l12 12"/>',
		'burger' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'alerte' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
		'phare' => '<path d="M10 2h4l1 5H9z"/><path d="M9 7 7.5 21h9L15 7"/><path d="M8.3 13h7.4M4 4l3 2M20 4l-3 2"/>',
		'maison' => '<path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-5h4v5"/>',
		'famille' => '<circle cx="8" cy="7" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-3.3 2.7-6 6-6s6 2.7 6 6M14 20c0-2.5 1.3-4.5 3-4.5s4 2 4 4.5"/>',
		'citoyen' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
		'signal' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><path d="M12 7v4M12 14h.01"/>',
		'asso' => '<path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
		'idee' => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"/>',
		'tel' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'horloge' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'lieu' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'haut' => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'gauche' => '<path d="M15 6l-6 6 6 6"/>',
		'droite' => '<path d="M9 6l6 6-6 6"/>',
		'etoile' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
		'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v5h1"/>',
		'repas' => '<path d="M4 3v8a3 3 0 0 0 6 0V3M7 3v18M17 21V3c-2.5 0-4 2.5-4 7v3h4"/>',
		'soleil' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'tasse' => '<path d="M17 8h1a4 4 0 0 1 0 8h-1M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4z"/><path d="M6 2v2M10 2v2M14 2v2"/>',
		'doc' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 14h6M9 17h4"/>',
		'check' => '<path d="m5 12 5 5L20 7"/>',
		'fb' => '<path d="M15 3h-3a4 4 0 0 0-4 4v3H5v4h3v7h4v-7h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'insta' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
		'yt' => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>',
		'ecole' => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/>',
		'sport' => '<circle cx="12" cy="12" r="9"/><path d="M12 3a14 14 0 0 1 0 18M3 12h18"/>',
		'arbre' => '<path d="M12 22v-6M7 16h10l-5-12z"/>',
		'bus' => '<rect x="4" y="3" width="16" height="15" rx="3"/><path d="M4 11h16M8 21v-3M16 21v-3"/><path d="M8 15h.01M16 15h.01"/>',
		'calendrier' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'coeur' => '<path d="M12 20s-8-5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6-8 11-8 11z"/>',
		'poubelle' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
		'courriel' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'pmr' => '<circle cx="12" cy="4.5" r="1.8"/><path d="M10 8h3l-.5 5h4l2 6M12.5 13H9a4 4 0 1 0 3.9 5"/>',
	);
}

/**
 * Libellés des icônes, pour les listes de choix dans l'éditeur.
 *
 * @return array
 */
function vf_socle_icones_libelles() {
	return array(
		'chevron' => 'Chevron',
		'fleche' => 'Flèche',
		'loupe' => 'Loupe',
		'croix' => 'Croix',
		'burger' => 'Menu',
		'alerte' => 'Alerte',
		'phare' => 'Phare',
		'maison' => 'Maison',
		'famille' => 'Famille',
		'citoyen' => 'Formulaire',
		'signal' => 'Signalement',
		'asso' => 'Association',
		'idee' => 'Idée',
		'tel' => 'Téléphone',
		'horloge' => 'Horloge',
		'lieu' => 'Lieu',
		'haut' => 'Haut',
		'gauche' => 'Gauche',
		'droite' => 'Droite',
		'etoile' => 'Étoile',
		'info' => 'Information',
		'repas' => 'Repas',
		'soleil' => 'Soleil',
		'tasse' => 'Tasse',
		'doc' => 'Document',
		'check' => 'Coche',
		'fb' => 'Facebook',
		'insta' => 'Instagram',
		'yt' => 'YouTube',
		'ecole' => 'École',
		'sport' => 'Sport',
		'arbre' => 'Nature',
		'bus' => 'Transports',
		'calendrier' => 'Calendrier',
		'coeur' => 'Solidarité',
		'poubelle' => 'Déchets',
		'courriel' => 'Courriel',
		'pmr' => 'Accessibilité',
	);
}

/**
 * Renvoie le SVG d'une icône, décoratif (masqué aux lecteurs d'écran).
 *
 * @param string $nom    Nom de l'icône.
 * @param int    $taille Taille en pixels.
 * @param float  $trait  Épaisseur du trait.
 * @return string SVG sûr (contenu fixe défini ci-dessus).
 */
function vf_socle_icone( $nom, $taille = 24, $trait = 1.8 ) {
	$icones = vf_socle_icones();
	if ( ! isset( $icones[ $nom ] ) ) {
		$nom = 'fleche';
	}
	return sprintf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%2$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		(int) $taille,
		esc_attr( (string) (float) $trait ),
		$icones[ $nom ]
	);
}

/**
 * Icône choisie pour une page (panneau « Icône de la page »), « doc » par défaut.
 *
 * @param WP_Post|int $page Page.
 * @return string
 */
function vf_socle_icone_page( $page ) {
	$nom = (string) get_post_meta( is_object( $page ) ? $page->ID : (int) $page, 'vf_icone', true );
	return ( $nom && array_key_exists( $nom, vf_socle_icones_libelles() ) ) ? $nom : 'doc';
}
