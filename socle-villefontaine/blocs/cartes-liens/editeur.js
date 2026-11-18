/* Bloc « Cartes de liens ». */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/cartes-liens', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Disposition' },
            el(c.SelectControl, { label: 'Cartes par ligne', value: props.attributes.colonnes, options: [2, 3, 4].map(function (n) { return { value: n, label: String(n) }; }), onChange: function (v) { props.setAttributes({ colonnes: parseInt(v, 10) }); }, __nextHasNoMarginBottom: true })
          ),
          el(c.PanelBody, { title: 'Cartes' },
            el('p', null, 'Sans image, la carte affiche l’icône choisie.'),
            vf.liste(props, 'cartes', [{ cle: 'titre', libelle: 'Titre' }, { cle: 'texte', libelle: 'Texte court' }, { cle: 'url', libelle: 'Lien', type: 'url' }, { cle: 'image', libelle: 'Adresse de l’image (facultatif)', type: 'url' }, { cle: 'icone', libelle: 'Icône', type: 'icone' }], { max: 12, nomElement: 'Carte', ajouter: 'Ajouter une carte' })
          )
        ),
        vf.apercu('vf/cartes-liens', props, { vide: 'Ajoutez des cartes dans la colonne de droite.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
