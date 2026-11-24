/* Bloc « Grand visuel d'accueil » : réglages dans la colonne de droite, aperçu fidèle au centre. */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/heros', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Photo' },
            vf.image(props, 'imageId', 'Grande photo (format paysage, 1600 px de large minimum). Elle est décorative : pas de texte important dessus.')
          ),
          el(c.PanelBody, { title: 'Titre' },
            vf.texte(props, 'surtitre', 'Petite phrase au-dessus du titre'),
            vf.texte(props, 'titre', 'Titre'),
            vf.texte(props, 'titreAccent', 'Fin du titre (en dégradé de couleur)')
          ),
          el(c.PanelBody, { title: 'Info du jour', initialOpen: false },
            el('p', null, 'Laissez le texte vide pour masquer l’info du jour.'),
            vf.texte(props, 'flashTexte', 'Texte de l’info du jour'),
            vf.texte(props, 'flashUrl', 'Lien (facultatif)')
          ),
          el(c.PanelBody, { title: 'Recherches fréquentes', initialOpen: false },
            el('p', null, 'Astuce : l’adresse #panneau-demarches ouvre le panneau « Vos démarches ».'),
            vf.liste(props, 'suggestions', [
              { cle: 'texte', libelle: 'Texte' },
              { cle: 'url', libelle: 'Adresse du lien', type: 'url' }
            ], { max: 5, nomElement: 'Lien', ajouter: 'Ajouter un lien' })
          )
        ),
        vf.apercu('vf/heros', props)
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
