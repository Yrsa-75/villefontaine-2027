/* ==========================================================================
   Bloc « Panneau Vos démarches » : comportements côté visiteur.
   - ouverture depuis tout bouton aria-controls="panneau-demarches", tout lien
     [data-ouvre-demarches] ou l'événement « vf:ouvrir-demarches » (recherche de l'accueil) ;
   - boîte de dialogue accessible : focus piégé, Échap, retour du focus ;
   - profils en onglets (flèches, Début, Fin) ;
   - recherche instantanée dans toutes les démarches, avec surlignage.
   ========================================================================== */
(function () {
  'use strict';
  var panneau = document.getElementById('panneau-demarches');
  if (!panneau) return;
  var reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var boite = panneau.querySelector('.panneau__boite');
  var champ = panneau.querySelector('#recherche-demarches');
  var boutonsOuvrir = document.querySelectorAll('[aria-controls="panneau-demarches"]');
  var dernierFocus = null;

  function ouvrir(e) {
    if (e && e.preventDefault) e.preventDefault();
    document.dispatchEvent(new CustomEvent('vf:ouverture-demarches'));
    dernierFocus = document.activeElement;
    panneau.classList.add('est-ouvert');
    panneau.removeAttribute('aria-hidden');
    boutonsOuvrir.forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
    document.body.style.overflow = 'hidden';
    setTimeout(function () { champ.focus(); }, reduit ? 0 : 250);
  }
  function fermer() {
    panneau.classList.remove('est-ouvert');
    panneau.setAttribute('aria-hidden', 'true');
    boutonsOuvrir.forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    document.body.style.overflow = '';
    if (dernierFocus && document.body.contains(dernierFocus)) dernierFocus.focus();
  }
  boutonsOuvrir.forEach(function (b) { b.addEventListener('click', ouvrir); });
  document.querySelectorAll('[data-ouvre-demarches]').forEach(function (a) {
    a.setAttribute('aria-haspopup', 'dialog');
    a.addEventListener('click', ouvrir);
  });
  panneau.querySelectorAll('[data-fermer]').forEach(function (b) { b.addEventListener('click', fermer); });
  panneau.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { fermer(); return; }
    if (e.key !== 'Tab') return;
    var focusables = Array.prototype.filter.call(boite.querySelectorAll('a[href], button, input, [tabindex="0"]'), function (el) { return el.offsetParent !== null; });
    var premier = focusables[0], dernier = focusables[focusables.length - 1];
    if (e.shiftKey && document.activeElement === premier) { e.preventDefault(); dernier.focus(); }
    else if (!e.shiftKey && document.activeElement === dernier) { e.preventDefault(); premier.focus(); }
  });

  /* ---------- Profils « Je suis… » (onglets) ---------- */
  var onglets = Array.prototype.slice.call(panneau.querySelectorAll('[role="tab"]'));
  var profils = Array.prototype.slice.call(panneau.querySelectorAll('.panneau__profil'));
  var blocProfils = panneau.querySelector('.profils');
  var question = panneau.querySelector('.panneau__question');
  var resultats = panneau.querySelector('.panneau__resultats');
  var actif = 0;

  function afficherProfil(i, donnerFocus) {
    actif = i;
    onglets.forEach(function (o, k) {
      o.setAttribute('aria-selected', k === i ? 'true' : 'false');
      o.tabIndex = k === i ? 0 : -1;
    });
    profils.forEach(function (p, k) { p.hidden = k !== i; });
    if (donnerFocus) onglets[i].focus();
  }
  onglets.forEach(function (o, i) {
    o.addEventListener('click', function () { afficherProfil(i, false); });
    o.addEventListener('keydown', function (e) {
      var n = onglets.length, cible = null;
      if (e.key === 'ArrowRight' || e.key === 'ArrowDown') cible = (i + 1) % n;
      else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') cible = (i - 1 + n) % n;
      else if (e.key === 'Home') cible = 0;
      else if (e.key === 'End') cible = n - 1;
      if (cible !== null) { e.preventDefault(); afficherProfil(cible, true); }
    });
  });

  /* ---------- Recherche instantanée ---------- */
  var vide = panneau.querySelector('.panneau__vide');
  var lienTout = panneau.querySelector('[data-recherche-tout]');
  var annonce = panneau.querySelector('#demarches-annonce');
  var accueil = panneau.getAttribute('data-recherche-site') || '/';
  function normaliser(t) { return t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }
  panneau.querySelectorAll('.panneau__liste a .libelle').forEach(function (s) { s.dataset.texte = s.textContent; });

  function filtrer() {
    var saisie = champ.value.trim();
    var q = normaliser(saisie);
    var total = 0;
    blocProfils.hidden = !!q;
    question.hidden = !!q;
    panneau.classList.toggle('en-recherche', !!q);
    profils.forEach(function (p, k) {
      var nProfil = 0;
      p.querySelectorAll('.panneau__section').forEach(function (sec) {
        var nSec = 0;
        sec.querySelectorAll('li').forEach(function (li) {
          var lib = li.querySelector('.libelle');
          var brut = lib.dataset.texte;
          var idx = normaliser(brut).indexOf(q);
          var ok = !q || idx > -1;
          li.hidden = !ok;
          if (ok) nSec++;
          lib.textContent = '';
          if (q && idx > -1) {
            lib.appendChild(document.createTextNode(brut.slice(0, idx)));
            var m = document.createElement('mark'); m.textContent = brut.slice(idx, idx + q.length); lib.appendChild(m);
            lib.appendChild(document.createTextNode(brut.slice(idx + q.length)));
          } else {
            lib.textContent = brut;
          }
        });
        sec.hidden = nSec === 0;
        nProfil += nSec;
      });
      p.hidden = q ? nProfil === 0 : k !== actif;
      total += q ? nProfil : 0;
    });
    vide.hidden = !q || total !== 0;
    if (lienTout) lienTout.href = accueil + '?s=' + encodeURIComponent(saisie);
    resultats.hidden = !q || total === 0;
    resultats.textContent = q ? total + (total > 1 ? ' démarches trouvées' : ' démarche trouvée') : '';
    annonce.textContent = q ? (total ? resultats.textContent : 'Aucune démarche ne correspond') : '';
  }
  champ.addEventListener('input', filtrer);
  /* Entrée dans le champ : s'il y a des résultats on reste dans le panneau, sinon on cherche dans tout le site */
  panneau.querySelector('form').addEventListener('submit', function (e) {
    filtrer();
    if (vide.hidden) e.preventDefault();
  });
  panneau.querySelectorAll('[data-suggestion]').forEach(function (b) {
    b.addEventListener('click', function () { champ.value = b.getAttribute('data-suggestion'); filtrer(); champ.focus(); });
  });

  /* ---------- Ouverture demandée par un autre bloc (ex. recherche de l'accueil) ---------- */
  document.addEventListener('vf:ouvrir-demarches', function (e) {
    champ.value = (e.detail && e.detail.recherche) || '';
    ouvrir();
    filtrer();
  });
})();
