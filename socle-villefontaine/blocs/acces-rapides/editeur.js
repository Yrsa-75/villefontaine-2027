/* Bloc « Accès en 1 clic » : liste des tuiles dans la colonne de droite, aperçu au centre. */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/acces-rapides', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Titre (lu par les lecteurs d’écran)' },
            vf.texte(props, 'titre', 'Titre de la zone', 'Ce titre n’est pas visible à l’écran, mais il aide les personnes aveugles à se repérer.')
          ),
          el(c.PanelBody, { title: 'Tuiles (8 maximum)' },
            vf.liste(props, 'liens', [
              { cle: 'libelle', libelle: 'Libellé (court)' },
              { cle: 'url', libelle: 'Adresse du lien', type: 'url' },
              { cle: 'icone', libelle: 'Icône', type: 'icone' }
            ], { max: 8, nomElement: 'Tuile', ajouter: 'Ajouter une tuile' })
          )
        ),
        vf.apercu('vf/acces-rapides', props)
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
