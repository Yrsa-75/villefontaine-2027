/* Bloc « Schéma interactif » : image, points numérotés, règles et sigles, réglés dans la colonne de droite. */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/schema-interactif', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Image' },
            vf.image(props, 'imageId', 'Illustration du schéma (format paysage conseillé)'),
            el('div', { style: { marginTop: '12px' } }, vf.texte(props, 'imageAlt', 'Description courte de l’image', 'Une phrase qui dit ce que montre l’image. Les règles, elles, sont dans les fiches.'))
          ),
          el(c.PanelBody, { title: 'Textes', initialOpen: false },
            vf.texte(props, 'consigne', 'Consigne au-dessus de l’image'),
            el(c.TextareaControl, { label: 'Rappel (encadré jaune sous l’image)', help: 'Laissez vide pour ne pas l’afficher.', rows: 3, value: props.attributes.rappel || '', onChange: function (v) { props.setAttributes({ rappel: v }); }, __nextHasNoMarginBottom: true }),
            vf.texte(props, 'titreListe', 'Titre de la liste complète', 'Laissez vide pour ne pas afficher de titre.'),
            el(c.SelectControl, { label: 'Niveau de ce titre', value: props.attributes.niveau, options: [{ value: 2, label: 'Titre 2' }, { value: 3, label: 'Titre 3 (sous un intertitre)' }], onChange: function (v) { props.setAttributes({ niveau: parseInt(v, 10) }); }, __nextHasNoMarginBottom: true })
          ),
          el(c.PanelBody, { title: 'Sigles expliqués', initialOpen: false },
            el('p', null, 'Chaque sigle écrit dans une règle (ex. DPC) devient une pastille : sa signification s’affiche au survol, et un clic ouvre la démarche.'),
            vf.liste(props, 'sigles', [
              { cle: 'sigle', libelle: 'Sigle', aide: 'Tel qu’il est écrit dans les règles, ex. DPC.' },
              { cle: 'libelle', libelle: 'Signification' },
              { cle: 'url', libelle: 'Adresse de la démarche (facultatif)', type: 'url' },
              { cle: 'lienTexte', libelle: 'Texte du lien', aide: 'Ex. « Démarche et formulaire ».' }
            ], { max: 12, nomElement: 'Sigle', cleTitre: 'sigle', ajouter: 'Ajouter un sigle' })
          ),
          el(c.PanelBody, { title: 'Points du schéma', initialOpen: false },
            el('p', null, 'Les points sont numérotés dans l’ordre de cette liste. Règles : une par ligne, sous la forme « condition : dossier » (ex. « ≤ 20 m² : DPC »). Une ligne qui commence par # devient un intertitre.'),
            vf.liste(props, 'points', [
              { cle: 'titre', libelle: 'Titre' },
              { cle: 'regles', libelle: 'Règles', type: 'zone', lignes: 4 },
              { cle: 'x', libelle: 'Position horizontale (%)', type: 'nombre', min: 0, max: 100, aide: '0 = bord gauche de l’image, 100 = bord droit.' },
              { cle: 'y', libelle: 'Position verticale (%)', type: 'nombre', min: 0, max: 100, aide: '0 = haut de l’image, 100 = bas.' },
              { cle: 'lienTexte', libelle: 'Texte d’un lien propre à ce point (facultatif)' },
              { cle: 'lienUrl', libelle: 'Adresse de ce lien', type: 'url' }
            ], { max: 30, nomElement: 'Point', cleTitre: 'titre', ajouter: 'Ajouter un point' })
          )
        ),
        vf.apercu('vf/schema-interactif', props, { vide: 'Schéma interactif : choisissez une image et ajoutez des points dans la colonne de droite.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
