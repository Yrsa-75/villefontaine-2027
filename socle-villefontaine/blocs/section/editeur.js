/* Bloc « Section avec titre » : le titre se tape directement, le contenu est libre (autres blocs). */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components, be = wp.blockEditor;
  wp.blocks.registerBlockType('vf/section', {
    edit: function (props) {
      var a = props.attributes;
      var blockProps = be.useBlockProps({ className: 'conteneur section' + (a.fond === 'bleu' ? ' vf-section--bleu' : ''), style: a.fond === 'bleu' ? { background: '#F4F9FA', paddingBottom: '48px' } : {} });
      return el(vf.Fragment, null,
        el(be.InspectorControls, null,
          el(c.PanelBody, { title: 'Réglages de la section' },
            vf.texte(props, 'lienTexte', 'Texte du lien « Tout voir »', 'Exemple : Toutes les actualités. Laissez vide pour ne pas afficher de lien.'),
            vf.texte(props, 'lienUrl', 'Adresse du lien'),
            el(c.SelectControl, { label: 'Fond', value: a.fond, options: [{ value: 'blanc', label: 'Blanc' }, { value: 'bleu', label: 'Bleu pâle, pleine largeur' }], onChange: function (v) { props.setAttributes({ fond: v }); }, __nextHasNoMarginBottom: true })
          )
        ),
        el('section', blockProps,
          el('div', { className: 'section__tete' },
            el('div', null,
              el(be.RichText, { tagName: 'p', className: 'section__surtitre', placeholder: 'Surtitre (facultatif)', value: a.surtitre, allowedFormats: [], onChange: function (v) { props.setAttributes({ surtitre: v }); } }),
              el(be.RichText, { tagName: 'h2', placeholder: 'Titre de la section', value: a.titre, allowedFormats: [], onChange: function (v) { props.setAttributes({ titre: v }); } })
            ),
            a.lienTexte ? el('span', { className: 'lien-fleche' }, el('span', null, a.lienTexte), ' →') : null
          ),
          el(be.InnerBlocks, { renderAppender: be.InnerBlocks.ButtonBlockAppender })
        )
      );
    },
    save: function () { return el(wp.blockEditor.InnerBlocks.Content); }
  });
})(window.wp, window.vfSocle);
