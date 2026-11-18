/* Bloc « Pages de la rubrique » : contenu automatique, l'éditeur montre un aperçu fidèle. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/sous-pages', {
    edit: function (props) { return vf.apercu('vf/sous-pages', props, { vide: 'Pages de la rubrique : les sous-pages de cette page apparaîtront ici.' }); },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
