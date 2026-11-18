/* Bloc « Infos de l'événement » : contenu automatique. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/evenement-infos', {
    edit: function (props) { return vf.apercu('vf/evenement-infos', props, { vide: 'Infos de l’événement (date, horaires, lieu).' }); },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
