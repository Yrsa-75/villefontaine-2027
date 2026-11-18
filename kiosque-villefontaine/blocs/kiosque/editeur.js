/* Bloc « Kiosque » : nombre, type et présentation dans la colonne de droite, aperçu au centre. */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/kiosque', {
    edit: function (props) {
      var a = props.attributes;
      var types = wp.data.useSelect(function (s) { return s('core').getEntityRecords('taxonomy', 'vf_type_publication', { per_page: 100 }); }, []) || [];
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Réglages' },
            el(c.SelectControl, { label: 'Présentation', value: a.format, options: [{ value: 'eventail', label: 'Éventail (accueil)' }, { value: 'grille', label: 'Grille' }], onChange: function (v) { props.setAttributes({ format: v }); }, __nextHasNoMarginBottom: true }),
            el(c.RangeControl, { label: 'Nombre de publications', value: a.nombre, min: 3, max: a.format === 'grille' ? 48 : 7, onChange: function (v) { props.setAttributes({ nombre: v }); }, __nextHasNoMarginBottom: true }),
            el(c.SelectControl, { label: 'Type de publication', value: a.type, options: [{ value: 0, label: 'Tous les types' }].concat(types.map(function (t) { return { value: t.id, label: t.name }; })), onChange: function (v) { props.setAttributes({ type: parseInt(v, 10) }); }, __nextHasNoMarginBottom: true })
          )
        ),
        vf.apercu('vf/kiosque', props, { vide: 'Aucune publication pour le moment.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
