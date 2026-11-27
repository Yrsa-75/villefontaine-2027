/* Bloc « Pages de la rubrique » : contenu automatique, l'éditeur montre un aperçu fidèle. */
(function (wp, vf) {
  'use strict';
  wp.blocks.registerBlockType('vf/sous-pages', {
    edit: function (props) {
      return vf.el(vf.Fragment, null,
        vf.el(wp.blockEditor.InspectorControls, null,
          vf.el(wp.components.PanelBody, { title: 'Titre' },
            vf.texte(props, 'titre', 'Titre au-dessus des pavés'),
            vf.el(wp.components.ToggleControl, {
              label: 'Masquer le titre à l’écran',
              help: 'À cocher quand la page ne contient que les pavés. Le titre reste lu par les lecteurs d’écran.',
              checked: !!props.attributes.titreMasque,
              onChange: function (v) { props.setAttributes({ titreMasque: v }); },
              __nextHasNoMarginBottom: true
            })
          )
        ),
        vf.apercu('vf/sous-pages', props, { vide: 'Pages de la rubrique : les sous-pages de cette page apparaîtront ici.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
