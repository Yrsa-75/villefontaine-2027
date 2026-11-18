/* Bloc « En-tête du site » : aperçu fidèle + rappel de l'endroit où l'on modifie le menu. */
(function (wp, vf) {
  'use strict';
  var el = vf.el;
  wp.blocks.registerBlockType('vf/entete', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(wp.components.PanelBody, { title: 'Contenu du menu' },
            el('p', null, 'Les rubriques du méga-menu se modifient dans Apparence > Menus (emplacement « Méga-menu principal »).'),
            el('a', { href: 'nav-menus.php', target: '_blank', rel: 'noopener' }, 'Ouvrir la gestion des menus')
          )
        ),
        vf.apercu('vf/entete', props, { vide: 'En-tête du site' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
