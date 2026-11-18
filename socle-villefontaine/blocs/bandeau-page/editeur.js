/* Bloc « Bandeau de page » : contenu automatique, l'éditeur montre un aperçu fidèle. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/bandeau-page', {
    edit: function (props) { return vf.apercu('vf/bandeau-page', props, { vide: 'Bandeau de page : titre, résumé et image mise en avant de la page.' }); },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
