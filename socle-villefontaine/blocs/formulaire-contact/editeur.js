/* Bloc « Formulaire de contact ». */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/formulaire-contact', {
    edit: function (props) {
      var a = props.attributes;
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Textes' },
            el(c.TextControl, { label: 'Titre', value: a.titre, onChange: function (v) { props.setAttributes({ titre: v }); }, __nextHasNoMarginBottom: true }),
            el(c.TextareaControl, { label: 'Introduction (facultatif)', value: a.intro, onChange: function (v) { props.setAttributes({ intro: v }); }, __nextHasNoMarginBottom: true })
          ),
          el(c.PanelBody, { title: 'Services destinataires' },
            el('p', null, 'Le visiteur choisit le service à contacter. Les adresses ne sont jamais affichées sur le site. Sans service renseigné, les messages partent à l’adresse de l’administrateur du site.'),
            vf.liste(props, 'destinataires', [{ cle: 'libelle', libelle: 'Nom du service (ex. État civil)' }, { cle: 'courriel', libelle: 'Adresse de réception' }], { max: 30, nomElement: 'Service', ajouter: 'Ajouter un service' })
          )
        ),
        vf.apercu('vf/formulaire-contact', props)
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.vfSocle);
