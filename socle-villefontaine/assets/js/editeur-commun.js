/* ==========================================================================
   Socle Villefontaine : boîte à outils commune aux blocs dans l'éditeur.
   Écrit sans étape de compilation (fonctions globales de WordPress), pour que
   n'importe quel développeur puisse le relire et le modifier directement.
   ========================================================================== */
(function (wp) {
  'use strict';
  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var be = wp.blockEditor;
  var c = wp.components;
  var ServerSideRender = wp.serverSideRender;

  var icones = (window.vfSocleDonnees && window.vfSocleDonnees.icones) || {};
  var optionsIcones = Object.keys(icones).map(function (k) { return { value: k, label: icones[k] }; });

  /**
   * Aperçu fidèle d'un bloc : le serveur produit le même HTML que sur le site.
   */
  function apercu(nom, props, extra) {
    return el('div', be.useBlockProps({ className: 'vf-apercu' }),
      el(ServerSideRender, {
        block: nom,
        attributes: props.attributes,
        httpMethod: 'POST',
        EmptyResponsePlaceholder: function () {
          return el(c.Placeholder, { label: extra && extra.vide ? extra.vide : 'Ce bloc s’affichera sur le site.' });
        }
      })
    );
  }

  /**
   * Liste modifiable (ajouter, monter, descendre, supprimer) dans la colonne de réglages.
   * champs : [{ cle, libelle, type: 'texte' | 'url' | 'icone' | 'zone' (plusieurs lignes) | 'nombre', aide, lignes, min, max }]
   */
  function liste(props, cle, champs, options) {
    options = options || {};
    var items = props.attributes[cle] || [];
    function maj(nouveaux) { var o = {}; o[cle] = nouveaux; props.setAttributes(o); }
    function modifie(i, champ, valeur) {
      var copie = items.map(function (it) { return Object.assign({}, it); });
      copie[i][champ] = valeur;
      maj(copie);
    }
    function deplace(i, sens) {
      var j = i + sens;
      if (j < 0 || j >= items.length) return;
      var copie = items.slice();
      var t = copie[i]; copie[i] = copie[j]; copie[j] = t;
      maj(copie);
    }
    var max = options.max || 99;

    return el(Fragment, null,
      items.map(function (it, i) {
        return el('div', { key: i, className: 'vf-liste-item', style: { borderTop: '1px solid #ddd', paddingTop: '12px', marginTop: '12px' } },
          el('p', { style: { fontWeight: 600, margin: '0 0 8px' } }, (options.nomElement || 'Élément') + ' ' + (i + 1) + (options.cleTitre && it[options.cleTitre] ? ' : ' + it[options.cleTitre] : '')),
          champs.map(function (ch) {
            if (ch.type === 'icone') {
              return el(c.SelectControl, { key: ch.cle, label: ch.libelle, value: it[ch.cle] || '', options: optionsIcones, onChange: function (v) { modifie(i, ch.cle, v); }, __nextHasNoMarginBottom: true });
            }
            if (ch.type === 'zone') {
              return el(c.TextareaControl, { key: ch.cle, label: ch.libelle, help: ch.aide, rows: ch.lignes || 4, value: it[ch.cle] || '', onChange: function (v) { modifie(i, ch.cle, v); }, __nextHasNoMarginBottom: true });
            }
            if (ch.type === 'nombre') {
              return el(c.TextControl, { key: ch.cle, label: ch.libelle, help: ch.aide, type: 'number', min: ch.min, max: ch.max, step: 0.5, value: it[ch.cle] === undefined ? '' : it[ch.cle], onChange: function (v) { modifie(i, ch.cle, v === '' ? '' : parseFloat(v)); }, __nextHasNoMarginBottom: true });
            }
            return el(c.TextControl, { key: ch.cle, label: ch.libelle, help: ch.aide, type: ch.type === 'url' ? 'url' : 'text', value: it[ch.cle] || '', onChange: function (v) { modifie(i, ch.cle, v); }, __nextHasNoMarginBottom: true });
          }),
          el(c.Flex, { justify: 'flex-start', style: { marginTop: '8px' } },
            el(c.Button, { size: 'small', variant: 'secondary', disabled: i === 0, onClick: function () { deplace(i, -1); } }, 'Monter'),
            el(c.Button, { size: 'small', variant: 'secondary', disabled: i === items.length - 1, onClick: function () { deplace(i, 1); } }, 'Descendre'),
            el(c.Button, { size: 'small', isDestructive: true, variant: 'tertiary', onClick: function () { maj(items.filter(function (_, k) { return k !== i; })); } }, 'Supprimer')
          )
        );
      }),
      items.length < max ? el(c.Button, { variant: 'primary', style: { marginTop: '14px' }, onClick: function () {
        var vide = {}; champs.forEach(function (ch) { vide[ch.cle] = ''; }); maj(items.concat([vide]));
      } }, options.ajouter || 'Ajouter') : el('p', null, 'Nombre maximum atteint (' + max + ').')
    );
  }

  /**
   * Choix d'une image de la médiathèque.
   */
  function image(props, cleId, libelle) {
    var id = props.attributes[cleId];
    var media = wp.data.useSelect(function (select) { return id ? select('core').getMedia(id) : null; }, [id]);
    return el(be.MediaUploadCheck, null,
      el(be.MediaUpload, {
        allowedTypes: ['image'],
        value: id,
        onSelect: function (m) { var o = {}; o[cleId] = m.id; props.setAttributes(o); },
        render: function (o) {
          return el('div', null,
            el('p', { style: { margin: '0 0 8px' } }, libelle),
            media && media.source_url ? el('img', { src: (media.media_details && media.media_details.sizes && media.media_details.sizes.medium ? media.media_details.sizes.medium.source_url : media.source_url), alt: '', style: { maxWidth: '100%', borderRadius: '8px', marginBottom: '8px' } }) : null,
            el(c.Flex, { justify: 'flex-start' },
              el(c.Button, { variant: 'secondary', onClick: o.open }, id ? 'Remplacer l’image' : 'Choisir une image'),
              id ? el(c.Button, { variant: 'tertiary', isDestructive: true, onClick: function () { var a = {}; a[cleId] = 0; props.setAttributes(a); } }, 'Retirer') : null
            )
          );
        }
      })
    );
  }

  /**
   * Champ texte simple lié à un attribut.
   */
  function texte(props, cle, libelle, aide) {
    return el(c.TextControl, { label: libelle, help: aide, value: props.attributes[cle] || '', onChange: function (v) { var o = {}; o[cle] = v; props.setAttributes(o); }, __nextHasNoMarginBottom: true });
  }

  window.vfSocle = { el: el, Fragment: Fragment, apercu: apercu, liste: liste, image: image, texte: texte };
})(window.wp);
