/* Bloc « Formulaire de contact ». */
(function (wp, vf) {
  'use strict';
  var el = vf.el, c = wp.components;
  wp.blocks.registerBlockType('vf/formulaire-contact', {
    edit: function (props) {
      var a = props.attributes;
      return el(vf.Fragment, null,
        el(wp.blockEditor.InspectorControls, null,
          el(c.PanelBody, { title: 'Type de formulaire' },
            el(c.SelectControl, { label: 'Modèle', help: 'Signalement : la liste devient « Nature du signalement », un champ « Lieu concerné » s’ajoute et l’objet du message est rédigé automatiquement.', value: a.modele || 'contact', options: [{ value: 'contact', label: 'Contact (écrire à un service)' }, { value: 'signalement', label: 'Signalement (problème sur l’espace public)' }], onChange: function (v) { props.setAttributes({ modele: v }); }, __nextHasNoMarginBottom: true })
          ),
          el(c.PanelBody, { title: 'Textes' },
            el(c.TextControl, { label: 'Titre', value: a.titre, onChange: function (v) { props.setAttributes({ titre: v }); }, __nextHasNoMarginBottom: true }),
            el(c.TextareaControl, { label: 'Introduction (facultatif)', value: a.intro, onChange: function (v) { props.setAttributes({ intro: v }); }, __nextHasNoMarginBottom: true })
          ),
          el(c.PanelBody, { title: a.modele === 'signalement' ? 'Natures de signalement et destinataires' : 'Services destinataires' },
            el('p', null, a.modele === 'signalement' ? 'Chaque ligne est un type de signalement proposé au visiteur, avec l’adresse du service qui le reçoit. ' : ''),
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
