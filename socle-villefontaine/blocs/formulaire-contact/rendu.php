<?php
/**
 * Bloc « Formulaire de contact ».
 * Le formulaire est envoyé en JavaScript à la route REST vf-socle/v1/contact.
 * Les adresses des services ne sont jamais écrites dans la page : seul leur numéro l'est.
 *
 * @package SocleVillefontaine
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide).
 * @var WP_Block $block      Bloc.
 */

defined( 'ABSPATH' ) || exit;

$vf_services = vf_socle_contact_services( (array) $attributes['destinataires'] );
// Modèle « signalement » : la liste déroulante devient la nature du problème, un champ « lieu » s'ajoute et l'objet est rédigé automatiquement.
$vf_sig      = 'signalement' === ( $attributes['modele'] ?? 'contact' );
$vf_titre    = $attributes['titre'] ? $attributes['titre'] : ( $vf_sig ? 'Faire un signalement' : 'Écrire à la mairie' );
$vf_id       = wp_unique_id( 'vf-form-' );
$vf_page_id  = vf_socle_post_du_bloc( $block );
$vf_rgpd     = get_privacy_policy_url();
if ( ! $vf_rgpd ) {
	$vf_p_rgpd = get_page_by_path( 'donnees-personnelles' );
	$vf_rgpd   = $vf_p_rgpd ? get_permalink( $vf_p_rgpd ) : '';
}

/**
 * Champ texte avec libellé, mention « obligatoire » et zone d'erreur.
 */
$vf_champ = static function ( $nom, $libelle, $type, $requis, $auto, $aide = '' ) use ( $vf_id ) {
	$id = $vf_id . '-' . $nom;
	$decrit = array();
	if ( $aide ) {
		$decrit[] = $id . '-aide';
	}
	$decrit[] = $id . '-erreur';
	echo '<div class="vf-form__champ">';
	echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $libelle ) . ( $requis ? ' <span class="vf-form__requis">(obligatoire)</span>' : '' ) . '</label>';
	if ( $aide ) {
		echo '<p class="vf-form__aide" id="' . esc_attr( $id ) . '-aide">' . esc_html( $aide ) . '</p>';
	}
	if ( 'textarea' === $type ) {
		echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $nom ) . '" rows="7" maxlength="5000"' . ( $requis ? ' required' : '' ) . ' aria-describedby="' . esc_attr( implode( ' ', $decrit ) ) . '"></textarea>';
	} else {
		echo '<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $nom ) . '" type="' . esc_attr( $type ) . '" maxlength="200"' . ( $auto ? ' autocomplete="' . esc_attr( $auto ) . '"' : '' ) . ( $requis ? ' required' : '' ) . ' aria-describedby="' . esc_attr( implode( ' ', $decrit ) ) . '">';
	}
	echo '<p class="vf-form__erreur" id="' . esc_attr( $id ) . '-erreur" hidden></p>';
	echo '</div>';
};
?>
<section class="vf-form" aria-labelledby="<?php echo esc_attr( $vf_id ); ?>-titre">
	<h2 id="<?php echo esc_attr( $vf_id ); ?>-titre"><?php echo esc_html( $vf_titre ); ?></h2>
	<?php if ( $attributes['intro'] ) : ?>
		<p class="vf-form__intro"><?php echo esc_html( $attributes['intro'] ); ?></p>
	<?php endif; ?>
	<noscript><p class="vf-form__noscript">Ce formulaire nécessite JavaScript. Vous pouvez aussi nous joindre au 04 74 96 00 00.</p></noscript>
	<form class="vf-form__formulaire" novalidate data-modele="<?php echo $vf_sig ? 'signalement' : 'contact'; ?>" data-route="<?php echo esc_url( rest_url( 'vf-socle/v1/contact' ) ); ?>" data-jeton="<?php echo esc_url( rest_url( 'vf-socle/v1/jeton' ) ); ?>">
		<div class="vf-form__resume" tabindex="-1" hidden></div>
		<p class="vf-form__legende">Les champs marqués « obligatoire » doivent être remplis.</p>
		<input type="hidden" name="page" value="<?php echo (int) $vf_page_id; ?>">
		<input type="hidden" name="jeton" value="">
		<div class="vf-form__piege" aria-hidden="true">
			<label for="<?php echo esc_attr( $vf_id ); ?>-site">Ne pas remplir ce champ</label>
			<input id="<?php echo esc_attr( $vf_id ); ?>-site" name="site_web" type="text" tabindex="-1" autocomplete="off">
		</div>
		<?php if ( count( $vf_services ) > 1 ) : ?>
			<div class="vf-form__champ">
				<label for="<?php echo esc_attr( $vf_id ); ?>-service"><?php echo $vf_sig ? 'Nature du signalement' : 'Service à contacter'; ?> <span class="vf-form__requis">(obligatoire)</span></label>
				<select id="<?php echo esc_attr( $vf_id ); ?>-service" name="service" required aria-describedby="<?php echo esc_attr( $vf_id ); ?>-service-erreur">
					<option value=""><?php echo $vf_sig ? 'Choisissez un type de signalement' : 'Choisissez un service'; ?></option>
					<?php foreach ( $vf_services as $vf_i => $vf_s ) : ?>
						<option value="<?php echo (int) $vf_i; ?>"><?php echo esc_html( $vf_s['libelle'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="vf-form__erreur" id="<?php echo esc_attr( $vf_id ); ?>-service-erreur" hidden></p>
			</div>
		<?php else : ?>
			<input type="hidden" name="service" value="0">
		<?php endif; ?>
		<?php if ( $vf_sig ) : ?>
			<?php $vf_champ( 'lieu', 'Lieu concerné', 'text', true, 'off', 'Adresse, ou repère le plus proche : école, arrêt de bus, parc…' ); ?>
			<?php $vf_champ( 'message', 'Description du problème', 'textarea', true, '' ); ?>
			<div class="vf-form__ligne">
				<?php $vf_champ( 'nom', 'Nom et prénom', 'text', true, 'name' ); ?>
				<?php $vf_champ( 'courriel', 'Adresse électronique', 'email', true, 'email', 'Format attendu : nom@exemple.fr' ); ?>
			</div>
			<?php $vf_champ( 'telephone', 'Téléphone', 'tel', false, 'tel', 'Format attendu : 06 12 34 56 78' ); ?>
		<?php else : ?>
		<div class="vf-form__ligne">
			<?php $vf_champ( 'nom', 'Nom et prénom', 'text', true, 'name' ); ?>
			<?php $vf_champ( 'courriel', 'Adresse électronique', 'email', true, 'email', 'Format attendu : nom@exemple.fr' ); ?>
		</div>
		<div class="vf-form__ligne">
			<?php $vf_champ( 'telephone', 'Téléphone', 'tel', false, 'tel', 'Format attendu : 06 12 34 56 78' ); ?>
			<?php $vf_champ( 'objet', 'Objet de votre message', 'text', true, 'off' ); ?>
		</div>
		<?php $vf_champ( 'message', 'Votre message', 'textarea', true, '' ); ?>
		<?php endif; ?>
		<div class="vf-form__champ vf-form__case">
			<input id="<?php echo esc_attr( $vf_id ); ?>-accord" name="accord" type="checkbox" value="1" required aria-describedby="<?php echo esc_attr( $vf_id ); ?>-accord-erreur">
			<label for="<?php echo esc_attr( $vf_id ); ?>-accord">J’accepte que la mairie utilise ces informations pour <?php echo $vf_sig ? 'traiter mon signalement' : 'répondre à ma demande'; ?>. Elles sont conservées 12 mois au plus. <span class="vf-form__requis">(obligatoire)</span></label>
			<p class="vf-form__erreur" id="<?php echo esc_attr( $vf_id ); ?>-accord-erreur" hidden></p>
		</div>
		<?php if ( $vf_rgpd ) : ?>
			<p class="vf-form__rgpd"><a href="<?php echo esc_url( $vf_rgpd ); ?>">En savoir plus sur vos données personnelles et vos droits</a></p>
		<?php endif; ?>
		<button type="submit" class="bouton"><?php echo $vf_sig ? 'Envoyer le signalement' : 'Envoyer le message'; ?> <?php echo vf_socle_icone( 'fleche', 18, 2.2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<p class="vf-form__statut" role="status" aria-live="polite"></p>
	</form>
</section>
