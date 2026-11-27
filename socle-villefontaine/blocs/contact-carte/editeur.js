/* Bloc « Carte contact ». */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/contact-carte', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Coordonnées' },
            vf.texte(props, 'titre', 'Nom du service ou du lieu'),
            vf.texte(props, 'texte', 'Phrase d’introduction (facultatif)'),
            vf.texte(props, 'adresse', 'Adresse'),
            vf.texte(props, 'tel', 'Téléphone'),
            vf.texte(props, 'courriel', 'Courriel'),
            vf.texte(props, 'horaires', 'Horaires'),
            vf.texte(props, 'lien', 'Lien (site internet, prise de rendez-vous…)'),
            vf.texte(props, 'lienTexte', 'Texte du lien (« Site internet » si vide)'),
            el(c.SelectControl, {
              label: 'Le nom est…',
              help: 'Dans un annuaire de plusieurs cartes, choisissez un titre : les lecteurs d’écran peuvent alors passer d’un service à l’autre.',
              value: String(props.attributes.niveau || 0),
              options: [{ label: 'un texte en gras', value: '0' }, { label: 'un titre de niveau 2', value: '2' }, { label: 'un titre de niveau 3', value: '3' }],
              onChange: function (v) { props.setAttributes({ niveau: parseInt(v, 10) || 0 }); },
              __nextHasNoMarginBottom: true
            })
          )
        ),
        vf.apercu('vf/contact-carte', props, { vide: 'Saisissez les coordonnées dans la colonne de droite.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
