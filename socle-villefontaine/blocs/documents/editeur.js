/* Bloc « Documents à télécharger » : liste modifiable dans la colonne de droite. */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/documents', {
    edit: function (props) {
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Titre (facultatif)' }, vf.texte(props, 'titre', 'Titre au-dessus de la liste', 'Ex. Documents à télécharger')),
          el(c.PanelBody, { title: 'Documents' },
            el('p', null, 'Astuce : copiez l’adresse du fichier depuis la médiathèque (« Copier l’URL »). Le type et le poids s’affichent automatiquement.'),
            vf.liste(props, 'documents', [{ cle: 'titre', libelle: 'Titre du document' }, { cle: 'url', libelle: 'Adresse du fichier', type: 'url' }], { nomElement: 'Document', ajouter: 'Ajouter un document' })
          )
        ),
        vf.apercu('vf/documents', props, { vide: 'Ajoutez des documents dans la colonne de droite.' })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
