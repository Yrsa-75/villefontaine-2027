/* Bloc « Carte des services » : textes et catégories dans la colonne de droite.
   Dans l'éditeur, la carte elle-même n'est pas chargée (aperçu simplifié). */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/carte-services', {
    edit: function (props) {
      var a = props.attributes;
      var cats = wp.data.useSelect(function (s) { return s('core').getEntityRecords('taxonomy', 'vf_categorie_lieu', { per_page: 100, hide_empty: false }); }, []) || [];
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Textes' },
            vf.texte(props, 'surtitre', 'Surtitre'),
            vf.texte(props, 'titre', 'Titre'),
            el(c.TextareaControl, { label: 'Texte', value: a.texte, onChange: function (v) { props.setAttributes({ texte: v }); }, __nextHasNoMarginBottom: true }),
            vf.texte(props, 'lienTexte', 'Texte du bouton (facultatif)', 'Ex. Ouvrir la carte en grand'),
            vf.texte(props, 'lienUrl', 'Adresse du bouton')
          ),
          el(c.PanelBody, { title: 'Affichage' },
            el(c.SelectControl, { label: 'Format', value: a.format, options: [{ value: 'encart', label: 'Encart (texte à gauche, carte à droite)' }, { value: 'pleine', label: 'Pleine largeur (page carte)' }], onChange: function (v) { props.setAttributes({ format: v }); }, __nextHasNoMarginBottom: true }),
            el('p', { style: { margin: '16px 0 6px', fontWeight: 600 } }, 'Catégories affichées (aucune cochée = toutes)'),
            cats.map(function (t) {
              var coche = a.categories.indexOf(t.id) > -1;
              return el(c.CheckboxControl, { key: t.id, label: t.name, checked: coche, onChange: function (v) {
                props.setAttributes({ categories: v ? a.categories.concat([t.id]) : a.categories.filter(function (x) { return x !== t.id; }) });
              }, __nextHasNoMarginBottom: true });
            })
          )
        ),
        vf.apercu('vf/carte-services', props, { vide: 'Aucun lieu avec coordonnées dans l’annuaire pour le moment.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
