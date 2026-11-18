/* Bloc « Chiffres clés ». */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/chiffres', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Chiffres (3 à 6 recommandés)' },
            vf.liste(props, 'chiffres', [{ cle: 'nombre', libelle: 'Chiffre', aide: 'Ex. 19 000 ou 69 %' }, { cle: 'libelle', libelle: 'Texte sous le chiffre' }], { max: 8, nomElement: 'Chiffre', ajouter: 'Ajouter un chiffre' })
          )
        ),
        vf.apercu('vf/chiffres', props, { vide: 'Saisissez les chiffres dans la colonne de droite.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
