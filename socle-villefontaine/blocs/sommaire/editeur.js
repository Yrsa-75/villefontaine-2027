/* Bloc « Sommaire de la page » : contenu automatique, l'éditeur montre un aperçu fidèle. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/sommaire', {
    edit: function (props) { return vf.apercu('vf/sommaire', props, { vide: 'Sommaire : il apparaît dès que la page contient au moins 2 intertitres de niveau 2.' }); },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
