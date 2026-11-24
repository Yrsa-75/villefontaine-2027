/* Recherche prédictive (accueil, page de résultats et champ de l'en-tête).
   Modèle « combobox » des recommandations d'accessibilité (ARIA APG) :
   - flèches bas / haut : se déplacer dans les propositions ; Entrée : ouvrir la proposition choisie ;
   - Échap : fermer la liste ; le nombre de propositions est annoncé aux lecteurs d'écran.
   Sans proposition choisie, Entrée garde le comportement normal du formulaire. */
(function () {
  'use strict';
  if (!window.vfSuggestions) return;
  if (window.vfSuggestions.css) {
    var feuille = document.createElement('link');
    feuille.rel = 'stylesheet';
    feuille.href = window.vfSuggestions.css;
    document.head.appendChild(feuille);
  }
  var TYPES = {
    demarche: 'Démarches',
    page: 'Pages',
    actualite: 'Actualités',
    evenement: 'Agenda',
    lieu: 'Lieux'
  };
  var ORDRE = ['demarche', 'page', 'evenement', 'actualite', 'lieu'];
  var MAX = 8;
  /* Mots du quotidien -> mots employés sur le site (la recherche essaie les deux). */
  var SYNONYMES = {
    'cantine': 'restauration scolaire', 'periscolaire': 'accueil de loisirs', 'centre de loisirs': 'accueil de loisirs',
    'creche': 'petite enfance', 'nounou': 'petite enfance', 'assistante maternelle': 'petite enfance',
    'poubelle': 'dechets', 'ordures': 'dechets', 'encombrants': 'decheteries', 'dechetterie': 'decheteries',
    'carte d identite': 'identite', 'cni': 'identite', 'passport': 'passeport',
    'bibliotheque': 'mediatheque', 'livres': 'mediatheque', 'piscine': 'sports', 'stade': 'sports', 'gymnase': 'sports',
    'emploi': 'recrute', 'job': 'recrute', 'travail': 'emploi', 'stage': 'recrute',
    'permis de construire': 'urbanisme', 'travaux': 'urbanisme', 'plu': 'plan local d urbanisme',
    'police': 'police municipale', 'gendarmerie': 'police', 'mutuelle': 'complementaire sante',
    'salle des fetes': 'reserver une salle', 'location de salle': 'reserver une salle', 'elections': 'citoyennete', 'vote': 'citoyennete',
    'maire': 'elus', 'conseillers': 'elus', 'cimetiere': 'cimetiere', 'deces': 'deces', 'naissance': 'naissance'
  };
  var index = null, chargement = null;

  function normaliser(t) { return (t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[’'`-]/g, ' '); }

  function charger() {
    if (index || chargement) return chargement;
    chargement = fetch(window.vfSuggestions.index, { credentials: 'omit' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        index = (Array.isArray(d) ? d : []).map(function (x) { x._l = normaliser(x.l); x._m = normaliser((x.c || '') + ' ' + (x.m || '')); return x; });
      })
      .catch(function () { index = []; });
    return chargement;
  }

  /* Score : tous les mots saisis doivent être trouvés ; titre commençant par la saisie > début de mot du titre > dans le titre > dans le complément. */
  function chercher(saisie) {
    var q = normaliser(saisie).trim();
    if (q.length < 2 || !index) return [];
    var variantes = [q];
    if (q.length >= 3) {
      Object.keys(SYNONYMES).forEach(function (k) { if (k.indexOf(q) === 0 || q.indexOf(k) === 0) variantes.push(SYNONYMES[k]); });
    }
    var meilleurs = {};
    variantes.forEach(function (v, n) {
      noter(v, n ? -1 : 0).forEach(function (r) {
        if (!meilleurs[r.x.u] || meilleurs[r.x.u].s < r.s) meilleurs[r.x.u] = r;
      });
    });
    var res = Object.keys(meilleurs).map(function (k) { return meilleurs[k]; });
    res.sort(function (a, b) { return b.s - a.s || a.x.l.length - b.x.l.length; });
    var garde = res.slice(0, MAX).map(function (r) { return r.x; });
    return ORDRE.reduce(function (acc, t) { return acc.concat(garde.filter(function (x) { return x.t === t; })); }, []);
  }

  function noter(q, bonus) {
    var mots = q.split(/\s+/);
    var res = [];
    index.forEach(function (x) {
      var score = 0;
      for (var i = 0; i < mots.length; i++) {
        var m = mots[i];
        var p = x._l.indexOf(m);
        if (p === 0) score += 6;
        else if (p > 0 && /\s/.test(x._l.charAt(p - 1))) score += 4;
        else if (p > 0) score += 2;
        else if ((' ' + x._m).indexOf(' ' + m) > -1) score += 1;
        else return;
      }
      if (x._l.indexOf(q) === 0) score += 4;
      res.push({ x: x, s: score + bonus });
    });
    return res;
  }

  function surligner(el, texte, saisie) {
    var q = normaliser(saisie).trim().split(/\s+/)[0];
    var p = q ? normaliser(texte).indexOf(q) : -1;
    if (p < 0) { el.textContent = texte; return; }
    el.appendChild(document.createTextNode(texte.slice(0, p)));
    var m = document.createElement('mark'); m.textContent = texte.slice(p, p + q.length); el.appendChild(m);
    el.appendChild(document.createTextNode(texte.slice(p + q.length)));
  }

  var compteur = 0;
  function installer(champ) {
    var form = champ.form;
    if (!form || champ.dataset.vfSuggestions) return;
    champ.dataset.vfSuggestions = '1';
    var id = 'vf-sugg-' + (++compteur);
    form.classList.add('vf-sugg-form');
    var liste = document.createElement('div');
    liste.className = 'vf-sugg';
    liste.id = id;
    liste.setAttribute('role', 'listbox');
    liste.setAttribute('aria-label', 'Propositions de recherche');
    liste.hidden = true;
    var statut = document.createElement('div');
    statut.className = 'visuellement-masque';
    statut.setAttribute('role', 'status');
    // La liste est placée à la fin de la page (et positionnée sous le champ) pour passer au-dessus de tout,
    // même quand le formulaire est dans une zone qui coupe ce qui dépasse (grand visuel de l'accueil).
    document.body.appendChild(liste);
    form.appendChild(statut);
    function positionner() {
      var r = form.getBoundingClientRect();
      liste.style.left = (r.left + window.scrollX) + 'px';
      liste.style.top = (r.bottom + window.scrollY + 8) + 'px';
      liste.style.width = r.width + 'px';
    }
    var attente = false;
    function suivre() {
      if (liste.hidden || attente) return;
      attente = true;
      requestAnimationFrame(function () { attente = false; positionner(); });
    }
    window.addEventListener('resize', suivre);
    window.addEventListener('scroll', suivre, { passive: true });
    champ.setAttribute('role', 'combobox');
    champ.setAttribute('aria-autocomplete', 'list');
    champ.setAttribute('aria-expanded', 'false');
    champ.setAttribute('aria-controls', id);
    champ.setAttribute('autocomplete', 'off');
    var options = [], actif = -1, minuterie;

    function fermer() {
      liste.hidden = true;
      champ.setAttribute('aria-expanded', 'false');
      champ.removeAttribute('aria-activedescendant');
      actif = -1;
    }
    function activer(i) {
      options.forEach(function (o, k) { o.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
      actif = i;
      if (i > -1) { champ.setAttribute('aria-activedescendant', options[i].id); options[i].scrollIntoView({ block: 'nearest' }); }
      else champ.removeAttribute('aria-activedescendant');
    }
    function afficher() {
      var saisie = champ.value;
      var res = chercher(saisie);
      liste.innerHTML = '';
      options = [];
      actif = -1;
      if (normaliser(saisie).trim().length < 2) { fermer(); statut.textContent = ''; return; }
      var groupe = null;
      res.forEach(function (x, k) {
        if (x.t !== groupe) {
          groupe = x.t;
          var g = document.createElement('div');
          g.className = 'vf-sugg__groupe';
          g.setAttribute('role', 'presentation');
          g.textContent = TYPES[x.t] || '';
          liste.appendChild(g);
        }
        var o = document.createElement('a');
        o.className = 'vf-sugg__option';
        o.id = id + '-' + k;
        o.href = x.u;
        o.setAttribute('role', 'option');
        o.setAttribute('aria-selected', 'false');
        o.tabIndex = -1;
        var l = document.createElement('span'); l.className = 'vf-sugg__libelle'; surligner(l, x.l, saisie); o.appendChild(l);
        if (x.c) { var c = document.createElement('span'); c.className = 'vf-sugg__complement'; c.textContent = x.c; o.appendChild(c); }
        o.addEventListener('mousedown', function (e) { e.preventDefault(); }); // garde le focus dans le champ
        liste.appendChild(o);
        options.push(o);
      });
      var tout = document.createElement('a');
      tout.className = 'vf-sugg__option vf-sugg__tout';
      tout.id = id + '-tout';
      tout.href = (form.getAttribute('action') || '/') + '?s=' + encodeURIComponent(saisie.trim());
      tout.setAttribute('role', 'option');
      tout.setAttribute('aria-selected', 'false');
      tout.tabIndex = -1;
      tout.textContent = 'Voir tous les résultats pour « ' + saisie.trim() + ' »';
      tout.addEventListener('mousedown', function (e) { e.preventDefault(); });
      liste.appendChild(tout);
      options.push(tout);
      positionner();
      liste.hidden = false;
      champ.setAttribute('aria-expanded', 'true');
      statut.textContent = res.length ? res.length + (res.length > 1 ? ' propositions. Flèche bas pour les parcourir.' : ' proposition. Flèche bas pour la choisir.') : 'Aucune proposition, appuyez sur Entrée pour chercher dans tout le site.';
    }

    champ.addEventListener('focus', charger);
    champ.addEventListener('input', function () {
      clearTimeout(minuterie);
      minuterie = setTimeout(function () {
        if (index) afficher(); else charger().then(afficher);
      }, 120);
    });
    champ.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        if (liste.hidden) { if (champ.value.trim().length >= 2) { afficher(); } return; }
        e.preventDefault(); activer(actif < options.length - 1 ? actif + 1 : 0);
      } else if (e.key === 'ArrowUp') {
        if (liste.hidden) return;
        e.preventDefault(); activer(actif > 0 ? actif - 1 : options.length - 1);
      } else if (e.key === 'Escape') {
        if (!liste.hidden) { e.preventDefault(); fermer(); }
      } else if (e.key === 'Enter' && actif > -1 && !liste.hidden) {
        e.preventDefault();
        e.stopImmediatePropagation();
        window.location.href = options[actif].href;
      }
    });
    champ.addEventListener('blur', function () { setTimeout(fermer, 150); });
    form.addEventListener('submit', fermer);
  }

  document.querySelectorAll('.immersif form input[name="s"], .search .wp-block-search input[name="s"], main .wp-block-search input[name="s"], .entete__recherche input[name="s"]').forEach(installer);
})();
