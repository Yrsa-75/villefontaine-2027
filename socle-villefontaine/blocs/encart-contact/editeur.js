/* Bloc « Encart contact de la page » : contenu automatique, l'éditeur montre un aperçu fidèle. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/encart-contact', {
    edit: function (props) { return vf.apercu('vf/encart-contact', props, { vide: 'Encart contact : remplissez le panneau « Encart contact » dans la colonne de droite de la page.' }); },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
