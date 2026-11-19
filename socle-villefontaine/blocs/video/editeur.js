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
            vf.texte(props, 'titre', 'Titre de la vidéo', 'Obligatoire : décrit la vidéo (ex. « Le jardin collectif de Vaugelas »). Lu par les lecteurs d’écran.'),
            el(c.TextareaControl, {
              label: 'Transcription textuelle',
              help: 'Texte intégral de ce qui est dit et montré. Obligatoire pour l’accessibilité (sauf vidéo publiée avant le 23/09/2020 ou produite par un tiers).',
              value: props.attributes.transcription || '',
              rows: 8,
              onChange: function (v) { props.setAttributes({ transcription: v }); }
            })
          )
        ),
        vf.apercu('vf/video', props, { vide: 'Collez l’adresse d’une vidéo YouTube ou Vimeo dans la colonne de droite.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
