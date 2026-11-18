/* Bloc « Dans la même rubrique » : aucun réglage, la liste suit l'arborescence des pages. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/meme-rubrique', {
    edit: function (props) { return vf.apercu('vf/meme-rubrique', props, { vide: 'Les autres pages de la même rubrique s’afficheront ici.' }); },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
