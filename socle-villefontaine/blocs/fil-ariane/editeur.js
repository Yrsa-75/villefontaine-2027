/* Bloc « Fil d’Ariane » : contenu automatique, l'éditeur montre un aperçu fidèle. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/fil-ariane', {
    edit: function (props) { return vf.apercu('vf/fil-ariane', props, { vide: 'Fil d’Ariane (Accueil > … > page en cours).' }); },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
