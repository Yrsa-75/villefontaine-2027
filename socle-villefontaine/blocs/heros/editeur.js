/* Bloc « Grand visuel d'accueil » : réglages dans la colonne de droite, aperçu fidèle au centre. */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/heros', {
    edit: function (props) {
      var suite = props.attributes.imagesSuite || [];
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Photo' },
            vf.image(props, 'imageId', 'Grande photo (format paysage, 2400 px de large conseillés). Elle est décorative : pas de texte important dessus.')
          ),
          el(c.PanelBody, { title: 'Diaporama', initialOpen: suite.length > 0 },
            el('p', null, 'Facultatif : d’autres photos qui s’enchaînent en fondu après la grande photo. Un bouton pause est ajouté automatiquement pour les visiteurs.'),
            el(wp.blockEditor.MediaUploadCheck, null,
              el(wp.blockEditor.MediaUpload, {
                allowedTypes: ['image'], multiple: true, gallery: true, value: suite,
                onSelect: function (medias) { props.setAttributes({ imagesSuite: medias.map(function (m) { return m.id; }) }); },
                render: function (o) {
                  return el(c.Flex, { justify: 'flex-start', wrap: true },
                    el(c.Button, { variant: 'secondary', onClick: o.open }, suite.length ? 'Modifier les photos (' + suite.length + ')' : 'Choisir les photos'),
                    suite.length ? el(c.Button, { variant: 'tertiary', isDestructive: true, onClick: function () { props.setAttributes({ imagesSuite: [] }); } }, 'Retirer le diaporama') : null
                  );
                }
              })
            ),
            suite.length ? el(c.RangeControl, { label: 'Durée d’affichage de chaque photo (secondes)', value: props.attributes.duree, min: 3, max: 15, onChange: function (v) { props.setAttributes({ duree: v || 5 }); }, __nextHasNoMarginBottom: true }) : null,
            suite.length ? el('p', { className: 'components-base-control__help' }, 'Le titre est écrit en blanc : évitez les photos très claires sur la moitié gauche.') : null
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
