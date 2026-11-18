/* Case « Mettre à la une » dans la colonne de droite de l'éditeur des articles. */
(function (wp) {
  'use strict';
  var el = wp.element.createElement;
  var Panneau = (wp.editor && wp.editor.PluginDocumentSettingPanel) || (wp.editPost && wp.editPost.PluginDocumentSettingPanel);
  if (!Panneau) return;
  function Une() {
    var type = wp.data.useSelect(function (s) { return s('core/editor').getCurrentPostType(); }, []);
    var meta = wp.data.useSelect(function (s) { return s('core/editor').getEditedPostAttribute('meta') || {}; }, []);
    var editer = wp.data.useDispatch('core/editor').editPost;
    if (type !== 'post') return null;
    return el(Panneau, { name: 'vf-a-la-une', title: 'Mise en avant' },
      el(wp.components.ToggleControl, {
        label: 'Mettre à la une',
        help: 'L’actualité à la une la plus récente s’affiche en grand sur l’accueil. Pensez à ajouter une image mise en avant.',
        checked: !!meta.vf_a_la_une,
        onChange: function (v) { editer({ meta: { vf_a_la_une: v } }); },
        __nextHasNoMarginBottom: true
      })
    );
  }
  wp.plugins.registerPlugin('vf-a-la-une', { render: Une });
})(window.wp);
