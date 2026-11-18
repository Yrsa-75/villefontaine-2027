/* Bloc « Agenda complet » : contenu automatique, aperçu fidèle. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/agenda-liste', {
    edit: function (props) { return vf.apercu('vf/agenda-liste', props, { vide: 'Aucun événement à venir pour le moment.' }); },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
