/* Bloc « Fiche du lieu » : contenu automatique. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/lieu-fiche', {
    edit: function (props) { return vf.apercu('vf/lieu-fiche', props, { vide: 'Coordonnées du lieu.' }); },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
