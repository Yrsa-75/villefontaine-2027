/* Bloc « Prochains événements » : réglages dans la colonne de droite, aperçu au centre. */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/agenda-prochains', {
    edit: function (props) {
      var themes = wp.data.useSelect(function (s) { return s('core').getEntityRecords('taxonomy', 'vf_theme_evenement', { per_page: 100 }); }, []) || [];
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Réglages' },
            el(c.SelectControl, { label: 'Nombre d’événements', value: props.attributes.nombre, options: [3, 6, 9].map(function (n) { return { value: n, label: String(n) }; }), onChange: function (v) { props.setAttributes({ nombre: parseInt(v, 10) }); }, __nextHasNoMarginBottom: true }),
            el(c.SelectControl, { label: 'Présentation', value: props.attributes.format, options: [{ value: 'cartes', label: 'Cartes avec image (accueil)' }, { value: 'liste', label: 'Liste compacte (pages thématiques)' }], onChange: function (v) { props.setAttributes({ format: v }); }, __nextHasNoMarginBottom: true }),
            props.attributes.format === 'liste' ? vf.texte(props, 'titre', 'Titre au-dessus de la liste', 'Ex. Prochains rendez-vous') : null,
            el(c.SelectControl, { label: 'Thématique', help: 'Pour une page de rubrique : n’afficher qu’une thématique (les filtres sont alors masqués).', value: props.attributes.thematique, options: [{ value: '', label: 'Toutes (avec filtres)' }].concat(themes.map(function (t) { return { value: t.slug, label: t.name }; })), onChange: function (v) { props.setAttributes({ thematique: v }); }, __nextHasNoMarginBottom: true })
          )
        ),
        vf.apercu('vf/agenda-prochains', props, { vide: 'Aucun événement à venir pour le moment.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
