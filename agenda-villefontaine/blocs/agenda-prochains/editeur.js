/* Bloc « Prochains événements » : réglages dans la colonne de droite, aperçu au centre. */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/agenda-prochains', {
    edit: function (props) {
      var themes = wp.data.useSelect(function (s) { return s('core').getEntityRecords('taxonomy', 'vf_theme_evenement', { per_page: 100 }); }, []) || [];
      var a = props.attributes;
      /* Thématiques cochées : la liste, ou l'ancien réglage à une seule thématique. */
      var choisies = (a.thematiques && a.thematiques.length) ? a.thematiques : (a.thematique ? [a.thematique] : []);
      function cocher(slug, oui) {
        var liste = choisies.filter(function (s) { return s !== slug; });
        if (oui) liste.push(slug);
        props.setAttributes({ thematiques: liste, thematique: '' });
      }
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Réglages' },
            el(c.SelectControl, { label: 'Nombre d’événements', value: a.nombre, options: [3, 6, 9, 12].map(function (n) { return { value: n, label: String(n) }; }), onChange: function (v) { props.setAttributes({ nombre: parseInt(v, 10) }); }, __nextHasNoMarginBottom: true }),
            el(c.SelectControl, { label: 'Présentation', value: a.format, options: [{ value: 'cartes', label: 'Cartes avec image' }, { value: 'liste', label: 'Liste compacte (pages thématiques)' }], onChange: function (v) { props.setAttributes({ format: v }); }, __nextHasNoMarginBottom: true }),
            vf.texte(props, 'titre', 'Titre au-dessus des événements', 'Facultatif. Ex. Prochains rendez-vous. À laisser vide sur l’accueil.')
          ),
          el(c.PanelBody, { title: 'Thématiques affichées' },
            el('fieldset', { style: { border: 0, margin: 0, padding: 0 } },
              el('legend', { className: 'screen-reader-text' }, 'Thématiques affichées'),
              themes.length ? themes.map(function (t) {
                return el(c.CheckboxControl, { key: t.slug, label: t.name, checked: choisies.indexOf(t.slug) !== -1, onChange: function (v) { cocher(t.slug, v); }, __nextHasNoMarginBottom: true });
              }) : el('p', null, 'Aucune thématique n’existe encore (Agenda > Thématiques).')
            ),
            el('p', { className: 'components-base-control__help', style: { marginTop: '12px' } }, 'Aucune case cochée : tous les événements, avec un bouton par thématique. Plusieurs cases : seuls ces événements, avec un bouton par thématique cochée. Une seule case : pas de bouton.')
          )
        ),
        vf.apercu('vf/agenda-prochains', props, { vide: 'Aucun événement à venir pour le moment.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
