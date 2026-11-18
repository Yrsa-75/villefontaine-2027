/* Bloc « Actualités en mosaïque » : nombre et catégorie dans la colonne de droite, aperçu au centre. */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/actualites-mosaique', {
    edit: function (props) {
      var cats = wp.data.useSelect(function (s) { return s('core').getEntityRecords('taxonomy', 'category', { per_page: 100, hide_empty: true }); }, []) || [];
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Réglages' },
            el(c.SelectControl, { label: 'Nombre d’actualités', value: props.attributes.nombre, options: [{ value: 3, label: '3' }, { value: 6, label: '6 (recommandé)' }], onChange: function (v) { props.setAttributes({ nombre: parseInt(v, 10) }); }, __nextHasNoMarginBottom: true }),
            el(c.SelectControl, { label: 'Catégorie', value: props.attributes.categorie, options: [{ value: 0, label: 'Toutes les catégories' }].concat(cats.map(function (t) { return { value: t.id, label: t.name }; })), onChange: function (v) { props.setAttributes({ categorie: parseInt(v, 10) }); }, __nextHasNoMarginBottom: true })
          )
        ),
        vf.apercu('vf/actualites-mosaique', props, { vide: 'Aucune actualité publiée pour le moment.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
