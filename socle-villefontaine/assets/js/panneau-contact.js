/* ==========================================================================
   Panneau « Encart contact » dans la colonne de droite de l'éditeur de pages.
   Les champs sont enregistrés avec la page ; l'encart s'affiche dans la colonne
   latérale du site dès qu'un champ est rempli.
   ========================================================================== */
(function (wp) {
  'use strict';
  var el = wp.element.createElement;
  var c = wp.components;
  var Panneau = (wp.editor && wp.editor.PluginDocumentSettingPanel) || (wp.editPost && wp.editPost.PluginDocumentSettingPanel);
  if (!Panneau) return;

  function Champs() {
    var type = wp.data.useSelect(function (s) { return s('core/editor').getCurrentPostType(); }, []);
    var meta = wp.data.useSelect(function (s) { return s('core/editor').getEditedPostAttribute('meta') || {}; }, []);
    var editer = wp.data.useDispatch('core/editor').editPost;
    if (type !== 'page') return null;
    function maj(cle, valeur) { var m = {}; m[cle] = valeur; editer({ meta: m }); }
    function texte(cle, libelle, aide, type) {
      return el(c.TextControl, { label: libelle, help: aide, type: type || 'text', value: meta[cle] || '', onChange: function (v) { maj(cle, v); }, __nextHasNoMarginBottom: true });
    }
    function zone(cle, libelle, aide) {
      return el(c.TextareaControl, { label: libelle, help: aide, rows: 3, value: meta[cle] || '', onChange: function (v) { maj(cle, v); }, __nextHasNoMarginBottom: true });
    }
    return el(Panneau, { name: 'vf-encart-contact', title: 'Encart contact', className: 'vf-encart-contact' },
      el('p', { style: { marginTop: 0 } }, 'Affiché dans la colonne de droite de la page. Laissez vide si la page n’a pas de contact.'),
      texte('vf_contact_titre', 'Titre', 'Exemple : Contacter le Phare'),
      zone('vf_contact_adresse', 'Adresse'),
      texte('vf_contact_tel', 'Téléphone', 'Exemple : 04 74 96 00 00', 'tel'),
      texte('vf_contact_courriel', 'Courriel', 'Protégé automatiquement contre les robots.', 'email'),
      texte('vf_contact_lien', 'Lien « Écrire au service »', 'Adresse d’un formulaire de contact (facultatif).', 'url'),
      zone('vf_contact_horaires', 'Horaires'),
      el(c.ToggleControl, { label: 'Accessible aux personnes à mobilité réduite', checked: !!meta.vf_contact_pmr, onChange: function (v) { maj('vf_contact_pmr', v); }, __nextHasNoMarginBottom: true })
    );
  }

  wp.plugins.registerPlugin('vf-encart-contact', { render: Champs });
})(window.wp);
