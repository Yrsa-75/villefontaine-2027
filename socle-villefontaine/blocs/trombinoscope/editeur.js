/* Bloc « Trombinoscope ». */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/trombinoscope', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Réglages' },
            el(c.TextControl, { label: 'Titre du groupe (ex. Les adjoints)', value: props.attributes.titre, onChange: function (v) { props.setAttributes({ titre: v }); }, __nextHasNoMarginBottom: true }),
            el(c.ToggleControl, { label: 'Mettre en avant (une seule personne, grand format)', checked: props.attributes.vedette, onChange: function (v) { props.setAttributes({ vedette: v }); }, __nextHasNoMarginBottom: true })
          ),
          el(c.PanelBody, { title: 'Personnes' },
            el('p', null, 'Sans photo, les initiales s’affichent.'),
            vf.liste(props, 'personnes', [{ cle: 'nom', libelle: 'Prénom et nom' }, { cle: 'fonction', libelle: 'Fonction ou délégation' }, { cle: 'mention', libelle: 'Mention (ex. Conseiller communautaire CAPI)' }, { cle: 'image', libelle: 'Adresse de la photo', type: 'url' }], { max: 40, nomElement: 'Personne', ajouter: 'Ajouter une personne' })
          )
        ),
        vf.apercu('vf/trombinoscope', props, { vide: 'Ajoutez des personnes dans la colonne de droite.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
