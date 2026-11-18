<?php
/**
 * Bloc « Panneau Vos démarches » : boîte de dialogue qui glisse depuis la droite.
 *
 * Données : menu affecté à l'emplacement « vf-demarches »
 * (1er niveau = profil, 2e = rubrique ou démarche, 3e = démarche).
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_profils = vf_socle_arbre_menu( 'vf-demarches' );
if ( ! $vf_profils ) {
	return; // Pas de menu : pas de panneau (et le bouton de l'en-tête n'est pas affiché non plus).
}

// Services en ligne (badge « En ligne ») et sites officiels externes (pictogramme).
$vf_en_ligne = apply_filters( 'vf_socle_domaines_en_ligne', array( 'espace-citoyen.fr', 'portailfamille.net', 'demarches-simplifiees.fr', 'simply-jobs.fr' ) );
$vf_externes = apply_filters( 'vf_socle_domaines_officiels', array( 'service-public.fr', 'impots.gouv.fr', 'ants.gouv.fr' ) );
$vf_icones_profil = array( 'vf-icone-maison' => 'maison', 'vf-icone-idee' => 'idee', 'vf-icone-asso' => 'asso', 'vf-icone-citoyen' => 'citoyen', 'vf-icone-famille' => 'famille', 'vf-icone-phare' => 'phare' );
$vf_defaut_icones = array( 'maison', 'idee', 'asso', 'citoyen' );

/**
 * Une démarche : lien + badge éventuel.
 */
$vf_demarche = static function ( $el ) use ( $vf_en_ligne, $vf_externes ) {
	$url   = $el['url'] ? $el['url'] : '#';
	$hote  = (string) wp_parse_url( $url, PHP_URL_HOST );
	$badge = '';
	foreach ( $vf_en_ligne as $d ) {
		if ( $hote && str_ends_with( $hote, $d ) ) {
			$badge = '<span class="badge-ligne">En ligne</span>';
			break;
		}
	}
	if ( ! $badge ) {
		foreach ( $vf_externes as $d ) {
			if ( $hote && str_ends_with( $hote, $d ) ) {
				$badge = '<span class="badge-ext">' . vf_socle_icone( 'fleche', 14, 2.2 ) . '<span class="visuellement-masque">(site externe)</span></span>';
				break;
			}
		}
	}
	return sprintf(
		'<li><a href="%s"%s><span class="libelle">%s</span>%s%s</a></li>',
		esc_url( $url ),
		vf_socle_attributs_lien( $url ),
		esc_html( $el['titre'] ),
		$badge,
		vf_socle_mention_lien( $url )
	);
};

$vf_total = 0;
foreach ( $vf_profils as $vf_p ) {
	foreach ( $vf_p['enfants'] as $vf_r ) {
		$vf_total += $vf_r['enfants'] ? count( $vf_r['enfants'] ) : 1;
	}
}

$vf_onglets  = '';
$vf_contenus = '';
foreach ( $vf_profils as $vf_k => $vf_p ) {
	$vf_cle   = 'p' . $vf_p['id'];
	$vf_actif = 0 === $vf_k;
	$vf_icone = $vf_defaut_icones[ $vf_k % 4 ];
	foreach ( $vf_p['classes'] as $vf_classe ) {
		if ( isset( $vf_icones_profil[ $vf_classe ] ) ) {
			$vf_icone = $vf_icones_profil[ $vf_classe ];
		}
	}
	// Regroupe : démarches directement sous le profil = rubrique sans titre.
	$vf_sections = array();
	$vf_libres   = array();
	foreach ( $vf_p['enfants'] as $vf_r ) {
		if ( $vf_r['enfants'] ) {
			if ( $vf_libres ) {
				$vf_sections[] = array( '', $vf_libres );
				$vf_libres     = array();
			}
			$vf_sections[] = array( $vf_r['titre'], $vf_r['enfants'] );
		} else {
			$vf_libres[] = $vf_r;
		}
	}
	if ( $vf_libres ) {
		$vf_sections[] = array( '', $vf_libres );
	}
	$vf_n = 0;
	$vf_blocs = '';
	foreach ( $vf_sections as $vf_s ) {
		$vf_lis = '';
		foreach ( $vf_s[1] as $vf_d ) {
			$vf_lis .= $vf_demarche( $vf_d );
			++$vf_n;
		}
		$vf_blocs .= '<div class="panneau__section">' . ( $vf_s[0] ? '<h3 class="panneau__sous-titre">' . esc_html( $vf_s[0] ) . '</h3>' : '' ) . '<ul class="panneau__liste">' . $vf_lis . '</ul></div>';
	}

	$vf_onglets .= sprintf(
		'<button type="button" role="tab" id="onglet-%1$s" aria-controls="profil-%1$s" aria-selected="%2$s" tabindex="%3$d" class="profil"><span class="icone">%4$s</span><span class="profil__nom">%5$s</span><span class="profil__compte">%6$s</span></button>',
		esc_attr( $vf_cle ),
		$vf_actif ? 'true' : 'false',
		$vf_actif ? 0 : -1,
		vf_socle_icone( $vf_icone, 22, 1.8 ),
		esc_html( $vf_p['titre'] ),
		esc_html( sprintf( _n( '%d démarche', '%d démarches', $vf_n, 'socle-villefontaine' ), $vf_n ) )
	);
	$vf_contenus .= sprintf(
		'<div class="panneau__profil" role="tabpanel" id="profil-%1$s" aria-labelledby="onglet-%1$s" tabindex="0"%2$s><h3 class="panneau__profil-titre">%3$s</h3>%4$s</div>',
		esc_attr( $vf_cle ),
		$vf_actif ? '' : ' hidden',
		esc_html( $vf_p['titre'] ),
		$vf_blocs
	);
}

$vf_rapides = array_slice( (array) $attributes['liensRapides'], 0, 4 );
$vf_suggest = array_slice( (array) $attributes['suggestions'], 0, 5 );
?>
<div class="panneau" id="panneau-demarches" aria-hidden="true" data-recherche-site="<?php echo esc_url( home_url( '/' ) ); ?>">
<div class="panneau__fond" data-fermer></div>
<div class="panneau__boite" role="dialog" aria-modal="true" aria-labelledby="titre-demarches">
<div class="panneau__tete">
<div class="panneau__titre"><h2 id="titre-demarches">Vos démarches</h2><button type="button" class="bouton-rond" data-fermer aria-label="Fermer le panneau des démarches"><?php echo vf_socle_icone( 'croix', 20, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button></div>
<form class="recherche" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>"><label class="visuellement-masque" for="recherche-demarches"><?php echo esc_html( sprintf( 'Rechercher parmi les %d démarches', $vf_total ) ); ?></label><input id="recherche-demarches" name="s" type="search" placeholder="Rechercher une démarche…" autocomplete="off"><button type="submit"><?php echo vf_socle_icone( 'loupe', 20, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="visuellement-masque">Rechercher</span></button></form>
<?php if ( $vf_suggest ) : ?>
<p class="panneau__frequent"><span>Les plus demandées :</span>
	<?php
	foreach ( $vf_suggest as $vf_s ) :
		if ( empty( $vf_s['libelle'] ) ) {
			continue;
		}
		?>
	<button type="button" data-suggestion="<?php echo esc_attr( $vf_s['recherche'] ?? $vf_s['libelle'] ); ?>"><?php echo esc_html( $vf_s['libelle'] ); ?></button>
	<?php endforeach; ?>
</p>
<?php endif; ?>
</div>
<div class="panneau__corps">
<?php if ( $vf_rapides ) : ?>
<div class="panneau__en-ligne">
	<?php
	foreach ( $vf_rapides as $vf_l ) :
		if ( empty( $vf_l['libelle'] ) ) {
			continue;
		}
		$vf_url = ! empty( $vf_l['url'] ) ? $vf_l['url'] : '#';
		?>
	<a href="<?php echo esc_url( $vf_url ); ?>"<?php echo vf_socle_attributs_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><span class="icone"><?php echo vf_socle_icone( $vf_l['icone'] ?? 'fleche', 20, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php echo esc_html( $vf_l['libelle'] ); ?><?php echo vf_socle_mention_lien( $vf_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	<?php endforeach; ?>
</div>
<?php endif; ?>
<h3 class="panneau__question" id="question-profil">Je suis…</h3>
<div class="profils" role="tablist" aria-labelledby="question-profil"><?php echo $vf_onglets; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé lors de la construction. ?></div>
<p id="demarches-annonce" class="visuellement-masque" aria-live="polite"></p>
<p class="panneau__resultats" hidden></p>
<?php echo $vf_contenus; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé lors de la construction. ?>
<div class="panneau__vide" hidden><p>Aucune démarche ne correspond.</p><p><a class="lien-fleche" data-recherche-tout href="<?php echo esc_url( home_url( '/' ) ); ?>"><span>Chercher dans tout le site</span> <?php echo vf_socle_icone( 'fleche', 16, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></p></div>
</div>
<p class="panneau__pied">Certaines démarches se font sur <a href="https://www.service-public.fr/" target="_blank" rel="noopener">service-public.fr<span class="visuellement-masque"> (nouvelle fenêtre)</span></a>, le site officiel de l’administration.</p>
</div>
</div>
