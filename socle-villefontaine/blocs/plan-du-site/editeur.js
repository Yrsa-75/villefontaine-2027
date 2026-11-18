/* Bloc « Plan du site » : aucun réglage, la liste se met à jour toute seule. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/plan-du-site', {
    edit: function (props) { return vf.apercu('vf/plan-du-site', props); },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
