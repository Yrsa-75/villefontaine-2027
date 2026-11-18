/* Bloc « Vidéo (avec consentement) ». */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/video', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Vidéo' },
            vf.texte(props, 'url', 'Adresse YouTube ou Vimeo'),
            vf.texte(props, 'titre', 'Titre de la vidéo', 'Lu par les lecteurs d’écran et affiché avant lecture.')
          )
        ),
        vf.apercu('vf/video', props, { vide: 'Collez l’adresse d’une vidéo YouTube ou Vimeo dans la colonne de droite.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
