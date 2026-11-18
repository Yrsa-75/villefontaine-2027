/* ==========================================================================
   Bloc « En-tête du site » : comportements côté visiteur.
   - en-tête qui se resserre au défilement ;
   - méga-menu : clic, clavier (Entrée, Espace, flèche bas), survol souris avec délai,
     Échap pour fermer et rendre le focus ;
   - menu mobile en boîte de dialogue (focus piégé, Échap pour fermer).
   ========================================================================== */
(function () {
  'use strict';
  var entete = document.querySelector('.entete');
  if (!entete) return;

  /* ---------- En-tête au défilement ---------- */
  function surDefilement() { entete.classList.toggle('est-defile', window.scrollY > 40); }
  window.addEventListener('scroll', surDefilement, { passive: true });
  surDefilement();

  /* ---------- Méga-menu ---------- */
  var voile = document.querySelector('.voile');
  var declencheurs = Array.prototype.slice.call(document.querySelectorAll('button.nav-principale__declencheur'));
  var ouvert = null;
  var minuterie = null;

  function panneauDe(bouton) { return document.getElementById(bouton.getAttribute('aria-controls')); }
  function ouvrir(bouton) {
    if (ouvert && ouvert !== bouton) fermer(ouvert, false);
    bouton.setAttribute('aria-expanded', 'true');
    panneauDe(bouton).classList.add('est-ouvert');
    if (voile) voile.classList.add('est-visible');
    entete.classList.add('menu-ouvert');
    ouvert = bouton;
  }
  function fermer(bouton, rendreFocus) {
    bouton.setAttribute('aria-expanded', 'false');
    panneauDe(bouton).classList.remove('est-ouvert');
    if (ouvert === bouton) {
      ouvert = null;
      if (voile) voile.classList.remove('est-visible');
      entete.classList.remove('menu-ouvert');
    }
    if (rendreFocus) bouton.focus();
  }

  declencheurs.forEach(function (bouton) {
    var panneau = panneauDe(bouton);
    var item = bouton.parentElement;
    if (!panneau) return;
    bouton.addEventListener('click', function () {
      bouton.getAttribute('aria-expanded') === 'true' ? fermer(bouton, false) : ouvrir(bouton);
    });
    bouton.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        ouvrir(bouton);
        var premier = panneau.querySelector('a');
        if (premier) premier.focus();
      }
    });
    /* Survol : uniquement à la souris, avec un délai pour éviter les ouvertures involontaires */
    item.addEventListener('pointerenter', function (e) {
      if (e.pointerType !== 'mouse') return;
      clearTimeout(minuterie);
      minuterie = setTimeout(function () { ouvrir(bouton); }, ouvert ? 0 : 140);
    });
    item.addEventListener('pointerleave', function (e) {
      if (e.pointerType !== 'mouse') return;
      clearTimeout(minuterie);
      minuterie = setTimeout(function () { if (ouvert === bouton) fermer(bouton, false); }, 220);
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && ouvert) fermer(ouvert, true);
  });
  document.addEventListener('focusin', function (e) {
    if (ouvert && !ouvert.parentElement.contains(e.target)) fermer(ouvert, false);
  });
  if (voile) voile.addEventListener('click', function () { if (ouvert) fermer(ouvert, false); });

  /* ---------- Menu mobile ---------- */
  var tiroir = document.getElementById('menu-mobile');
  var burger = document.querySelector('.burger');
  if (tiroir && burger) {
    var boutonFermer = tiroir.querySelector('.tiroir__fermer');
    function ouvrirTiroir() {
      tiroir.classList.add('est-ouvert');
      burger.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
      boutonFermer.focus();
    }
    function fermerTiroir(rendreFocus) {
      tiroir.classList.remove('est-ouvert');
      burger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
      if (rendreFocus !== false) burger.focus();
    }
    burger.addEventListener('click', ouvrirTiroir);
    boutonFermer.addEventListener('click', fermerTiroir);
    tiroir.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { fermerTiroir(); return; }
      if (e.key !== 'Tab') return;
      var focusables = Array.prototype.filter.call(tiroir.querySelectorAll('a[href], button, summary'), function (el) { return el.offsetParent !== null; });
      var premier = focusables[0], dernier = focusables[focusables.length - 1];
      if (e.shiftKey && document.activeElement === premier) { e.preventDefault(); dernier.focus(); }
      else if (!e.shiftKey && document.activeElement === dernier) { e.preventDefault(); premier.focus(); }
    });
    /* Le panneau « Vos démarches » referme le tiroir avant de s'ouvrir */
    document.addEventListener('vf:ouverture-demarches', function () {
      if (tiroir.classList.contains('est-ouvert')) fermerTiroir(false);
    });
  }
})();
