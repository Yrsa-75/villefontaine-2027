/* Bloc « Carte contact ». */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/contact-carte', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Coordonnées' },
            vf.texte(props, 'titre', 'Nom du service ou du lieu'),
            vf.texte(props, 'texte', 'Phrase d’introduction (facultatif)'),
            vf.texte(props, 'adresse', 'Adresse'),
            vf.texte(props, 'tel', 'Téléphone'),
            vf.texte(props, 'courriel', 'Courriel'),
            vf.texte(props, 'horaires', 'Horaires'),
            vf.texte(props, 'lien', 'Site internet')
          )
        ),
        vf.apercu('vf/contact-carte', props, { vide: 'Saisissez les coordonnées dans la colonne de droite.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
