/* Bloc « Panneau Vos démarches » : il est invisible tant qu'on ne clique pas sur le bouton,
   donc l'éditeur affiche un simple encart et les réglages dans la colonne de droite. */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/panneau-demarches', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Accès en ligne (4 maximum)' },
            vf.liste(props, 'liensRapides', [
              { cle: 'libelle', libelle: 'Libellé' },
              { cle: 'url', libelle: 'Adresse du lien', type: 'url' },
              { cle: 'icone', libelle: 'Icône', type: 'icone' }
            ], { max: 4, nomElement: 'Accès', ajouter: 'Ajouter un accès' })
          ),
          el(c.PanelBody, { title: 'Recherches les plus demandées', initialOpen: false },
            vf.liste(props, 'suggestions', [
              { cle: 'libelle', libelle: 'Texte du bouton' },
              { cle: 'recherche', libelle: 'Mot recherché', aide: 'Le mot tapé automatiquement dans la recherche du panneau.' }
            ], { max: 5, nomElement: 'Suggestion', ajouter: 'Ajouter une suggestion' })
          ),
          el(c.PanelBody, { title: 'Liste des démarches', initialOpen: false },
            el('p', null, 'Les démarches se modifient dans Apparence > Menus (emplacement « Panneau Vos démarches »).')
          )
        ),
        el('div', wp.blockEditor.useBlockProps(),
          el(c.Placeholder, { icon: 'index-card', label: 'Panneau « Vos démarches »', instructions: 'Ce panneau s’ouvre sur le site quand on clique sur le bouton vert « Vos démarches ». Ses réglages sont dans la colonne de droite.' })
        )
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
