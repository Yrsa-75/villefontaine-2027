<?php
/**
 * Calcul des dates d'un événement (répétitions comprises) et textes de dates en français.
 *
 * @package AgendaVillefontaine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Date du jour dans le fuseau du site (Y-m-d).
 *
 * @return string
 */
function vf_agenda_aujourdhui() {
	return wp_date( 'Y-m-d' );
}

/**
 * Liste les occurrences d'un événement entre deux dates.
 * Une occurrence : [ 'debut' => Y-m-d, 'fin' => Y-m-d ].
 *
 * @param int    $post_id Événement.
 * @param string $de      Début de la période (Y-m-d).
 * @param string $a       Fin de la période (Y-m-d).
 * @return array
 */
function vf_agenda_occurrences( $post_id, $de, $a ) {
	$debut = (string) get_post_meta( $post_id, 'vf_debut', true );
	if ( '' === $debut ) {
		return array();
	}
	$fin   = (string) get_post_meta( $post_id, 'vf_fin', true );
	$fin   = ( $fin && $fin >= $debut ) ? $fin : $debut;
	$duree = (int) round( ( strtotime( $fin ) - strtotime( $debut ) ) / DAY_IN_SECONDS );
	$rec   = (string) get_post_meta( $post_id, 'vf_recurrence', true );
	$rfin  = (string) get_post_meta( $post_id, 'vf_recurrence_fin', true );
	$rfin  = $rfin ? $rfin : gmdate( 'Y-m-d', strtotime( $debut . ' +1 year' ) );

	$liste = array();
	$ajoute = function ( $d ) use ( &$liste, $duree, $de, $a ) {
		$f = gmdate( 'Y-m-d', strtotime( $d . ' +' . $duree . ' days' ) );
		if ( $f >= $de && $d <= $a ) {
			$liste[] = array( 'debut' => $d, 'fin' => $f );
		}
	};

	if ( '' === $rec ) {
		$ajoute( $debut );
		return $liste;
	}

	$t     = strtotime( $debut . ' 12:00:00' );
	$limite = min( $rfin, $a );
	$garde  = 0;
	if ( 'hebdo' === $rec || 'quinzaine' === $rec ) {
		$pas = 'hebdo' === $rec ? 7 : 14;
		while ( gmdate( 'Y-m-d', $t ) <= $limite && $garde++ < 400 ) {
			$ajoute( gmdate( 'Y-m-d', $t ) );
			$t += $pas * DAY_IN_SECONDS;
		}
	} elseif ( 'mensuel_date' === $rec ) {
		$jour = (int) gmdate( 'j', $t );
		$mois = strtotime( gmdate( 'Y-m-01', $t ) . ' 12:00:00' );
		while ( gmdate( 'Y-m-d', $mois ) <= $limite && $garde++ < 60 ) {
			if ( $jour <= (int) gmdate( 't', $mois ) ) {
				$d = gmdate( 'Y-m-', $mois ) . sprintf( '%02d', $jour );
				if ( $d >= $debut && $d <= $limite ) {
					$ajoute( $d );
				}
			}
			$mois = strtotime( gmdate( 'Y-m-01', $mois ) . ' +1 month 12:00:00' );
		}
	} elseif ( 'mensuel_jour' === $rec ) {
		$rang   = (int) ceil( (int) gmdate( 'j', $t ) / 7 ); // 1er, 2e, 3e, 4e ou 5e.
		$jour_s = (int) gmdate( 'w', $t );                  // 0 = dimanche.
		$mois   = strtotime( gmdate( 'Y-m-01', $t ) . ' 12:00:00' );
		while ( gmdate( 'Y-m-d', $mois ) <= $limite && $garde++ < 60 ) {
			$premier = (int) gmdate( 'w', $mois );
			$j       = 1 + ( ( $jour_s - $premier + 7 ) % 7 ) + 7 * ( $rang - 1 );
			if ( $j <= (int) gmdate( 't', $mois ) ) {
				$d = gmdate( 'Y-m-', $mois ) . sprintf( '%02d', $j );
				if ( $d >= $debut && $d <= $limite ) {
					$ajoute( $d );
				}
			}
			$mois = strtotime( gmdate( 'Y-m-01', $mois ) . ' +1 month 12:00:00' );
		}
	}
	return $liste;
}

/**
 * Prochaines dates de l'agenda (tous événements confondus), triées.
 *
 * @param int    $limite    Nombre maximum d'occurrences.
 * @param int    $jours     Horizon en jours.
 * @param string $thematique Slug de thématique (vide = toutes).
 * @param bool   $une_fois  Vrai : un événement répété n'apparaît qu'une fois (sa prochaine date).
 * @return array Liste de [ 'post' => WP_Post, 'debut' => Y-m-d, 'fin' => Y-m-d ].
 */
function vf_agenda_prochaines( $limite = 6, $jours = 365, $thematique = '', $une_fois = false ) {
	$de   = vf_agenda_aujourdhui();
	$a    = gmdate( 'Y-m-d', strtotime( $de . ' +' . (int) $jours . ' days' ) );
	$args = array(
		'post_type'      => 'vf_evenement',
		'post_status'    => 'publish',
		'posts_per_page' => 300,
		'no_found_rows'  => true,
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery -- une seule condition sur une date, résultat mis en cache par LiteSpeed.
			array(
				'key'     => 'vf_fin_effective',
				'value'   => $de,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	);
	if ( $thematique ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'vf_theme_evenement', 'field' => 'slug', 'terms' => $thematique ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$resultat = array();
	foreach ( get_posts( $args ) as $post ) {
		foreach ( vf_agenda_occurrences( $post->ID, $de, $a ) as $occ ) {
			$resultat[] = array( 'post' => $post ) + $occ;
		}
	}
	usort(
		$resultat,
		static function ( $x, $y ) {
			$cle_x = max( $x['debut'], vf_agenda_aujourdhui() ) . ( get_post_meta( $x['post']->ID, 'vf_heure_debut', true ) ?: '99' );
			$cle_y = max( $y['debut'], vf_agenda_aujourdhui() ) . ( get_post_meta( $y['post']->ID, 'vf_heure_debut', true ) ?: '99' );
			return strcmp( $cle_x, $cle_y );
		}
	);
	if ( $une_fois ) {
		$vus      = array();
		$resultat = array_values(
			array_filter(
				$resultat,
				static function ( $occ ) use ( &$vus ) {
					if ( isset( $vus[ $occ['post']->ID ] ) ) {
						return false;
					}
					$vus[ $occ['post']->ID ] = true;
					return true;
				}
			)
		);
	}
	return array_slice( $resultat, 0, $limite );
}

/* ---------------------------------------------------------------------------
   Textes de dates en français (indépendants de la langue de l'administration)
   --------------------------------------------------------------------------- */

/**
 * Noms des jours et des mois.
 *
 * @param string $quoi « jours », « mois » ou « mois_courts ».
 * @return array
 */
function vf_agenda_noms( $quoi ) {
	$noms = array(
		'jours'       => array( 'dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi' ),
		'mois'        => array( '', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' ),
		'mois_courts' => array( '', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.' ),
	);
	return $noms[ $quoi ];
}

/**
 * « 15 h » ou « 10 h 30 ».
 *
 * @param string $h Heure H:i.
 * @return string
 */
function vf_agenda_heure( $h ) {
	if ( ! $h ) {
		return '';
	}
	list( $hh, $mm ) = array_map( 'intval', explode( ':', $h ) );
	return $hh . ' h' . ( $mm ? ' ' . sprintf( '%02d', $mm ) : '' );
}

/**
 * « 3 novembre » (+ année si différente de l'année en cours).
 *
 * @param string $d Date Y-m-d.
 * @return string
 */
function vf_agenda_date_texte( $d ) {
	$t     = strtotime( $d . ' 12:00:00' );
	$jour  = (int) gmdate( 'j', $t );
	$texte = ( 1 === $jour ? '1er' : $jour ) . ' ' . vf_agenda_noms( 'mois' )[ (int) gmdate( 'n', $t ) ];
	if ( gmdate( 'Y', $t ) !== wp_date( 'Y' ) ) {
		$texte .= ' ' . gmdate( 'Y', $t );
	}
	return $texte;
}

/**
 * Ligne « quand » d'une occurrence : « Mercredi, 15 h à 17 h », « Jusqu’au 28 novembre »…
 *
 * @param int   $post_id Événement.
 * @param array $occ     Occurrence.
 * @return string Texte brut (à échapper à l'affichage).
 */
function vf_agenda_quand( $post_id, $occ ) {
	$hd   = vf_agenda_heure( get_post_meta( $post_id, 'vf_heure_debut', true ) );
	$hf   = vf_agenda_heure( get_post_meta( $post_id, 'vf_heure_fin', true ) );
	$heures = $hd ? ( $hf ? $hd . ' à ' . $hf : 'à partir de ' . $hd ) : '';

	if ( $occ['fin'] !== $occ['debut'] ) {
		$texte = $occ['debut'] <= vf_agenda_aujourdhui() ? 'Jusqu’au ' . vf_agenda_date_texte( $occ['fin'] ) : 'Du ' . vf_agenda_date_texte( $occ['debut'] ) . ' au ' . vf_agenda_date_texte( $occ['fin'] );
	} else {
		$texte = ucfirst( vf_agenda_noms( 'jours' )[ (int) gmdate( 'w', strtotime( $occ['debut'] . ' 12:00' ) ) ] );
	}
	if ( $heures ) {
		$texte .= ', ' . $heures;
	}
	$rec = get_post_meta( $post_id, 'vf_recurrence', true );
	if ( $rec ) {
		$debut = strtotime( get_post_meta( $post_id, 'vf_debut', true ) . ' 12:00' );
		$jour  = vf_agenda_noms( 'jours' )[ (int) gmdate( 'w', $debut ) ];
		$rang  = (int) ceil( (int) gmdate( 'j', $debut ) / 7 );
		$suite = array(
			'hebdo'        => 'chaque ' . $jour,
			'quinzaine'    => 'un ' . $jour . ' sur deux',
			'mensuel_jour' => 'chaque ' . ( 1 === $rang ? '1er' : $rang . 'e' ) . ' ' . $jour . ' du mois',
			'mensuel_date' => 'chaque mois',
		);
		$texte .= ', ' . ( $suite[ $rec ] ?? '' );
	}
	return $texte;
}

/**
 * Carte d'un événement (accueil et page Agenda).
 *
 * @param array $occ   Occurrence (post, debut, fin).
 * @param int   $index Position (pour le léger décalage d'apparition).
 * @param int   $titre Niveau du titre (3 par défaut).
 * @return string HTML échappé.
 */
function vf_agenda_carte( $occ, $index = 0, $titre = 3 ) {
	$post   = $occ['post'];
	$termes = get_the_terms( $post, 'vf_theme_evenement' );
	$terme  = ( $termes && ! is_wp_error( $termes ) ) ? $termes[0] : null;
	$jour   = max( $occ['debut'], vf_agenda_aujourdhui() );
	$t      = strtotime( $jour . ' 12:00' );
	$image  = get_the_post_thumbnail( $post, 'medium_large', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 760px) 100vw, 33vw' ) );
	$lieu   = (string) get_post_meta( $post->ID, 'vf_lieu', true );
	$h      = 'h' . (int) $titre;

	$html  = '<li data-theme="' . esc_attr( $terme ? $terme->slug : 'autre' ) . '" class="apparition" style="--delai: ' . esc_attr( number_format( ( $index % 3 ) * 0.07, 2, '.', '' ) ) . 's"><article class="evenement">';
	$html .= '<div class="evenement__media">' . ( $image ? $image : '<span class="evenement__fond" aria-hidden="true">' . vf_socle_icone( 'calendrier', 40, 1.6 ) . '</span>' );
	$html .= '<p class="evenement__date" style="margin: 0"><strong>' . esc_html( gmdate( 'j', $t ) ) . '</strong><span>' . esc_html( vf_agenda_noms( 'mois_courts' )[ (int) gmdate( 'n', $t ) ] ) . '</span></p></div>';
	$html .= '<div class="evenement__corps"><' . $h . '><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></' . $h . '>';
	$html .= '<p class="evenement__infos"><span>' . vf_socle_icone( 'horloge', 16, 2 ) . esc_html( vf_agenda_quand( $post->ID, $occ ) ) . '</span>';
	if ( $lieu ) {
		$html .= '<span>' . vf_socle_icone( 'lieu', 16, 2 ) . esc_html( $lieu ) . '</span>';
	}
	$html .= '</p>';
	if ( $terme ) {
		$html .= '<p class="evenement__theme" style="margin-bottom: 0">' . esc_html( $terme->name ) . '</p>';
	}
	return $html . '</div></article></li>';
}

/**
 * Boutons de filtre par thématique (uniquement les thématiques présentes).
 *
 * @param array $occs Occurrences affichées.
 * @return string
 */
function vf_agenda_filtres( $occs ) {
	$themes = array();
	foreach ( $occs as $occ ) {
		$termes = get_the_terms( $occ['post'], 'vf_theme_evenement' );
		if ( $termes && ! is_wp_error( $termes ) ) {
			$themes[ $termes[0]->slug ] = $termes[0]->name;
		}
	}
	if ( count( $themes ) < 2 ) {
		return '';
	}
	asort( $themes );
	$html = '<div class="filtres apparition" role="group" aria-label="Filtrer les événements par thématique"><button type="button" data-filtre="tout" aria-pressed="true">Tout</button>';
	foreach ( $themes as $slug => $nom ) {
		$html .= '<button type="button" data-filtre="' . esc_attr( $slug ) . '" aria-pressed="false">' . esc_html( $nom ) . '</button>';
	}
	return $html . '</div>';
}
