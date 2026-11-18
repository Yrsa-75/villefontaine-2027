/* ==========================================================================
   Thème Villefontaine : petits comportements communs à toutes les pages.
   Sans bibliothèque. Les comportements des blocs sont dans leurs propres
   fichiers (plugin Socle Villefontaine) et ne se chargent que si besoin.
   ========================================================================== */
(function () {
  'use strict';
  var reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Bouton « Revenir en haut de la page » ---------- */
  var haut = document.createElement('button');
  haut.type = 'button';
  haut.className = 'haut-de-page';
  haut.setAttribute('aria-label', 'Revenir en haut de la page');
  haut.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg>';
  document.body.appendChild(haut);
  haut.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: reduit ? 'auto' : 'smooth' });
    var cible = document.querySelector('.lien-evitement') || document.querySelector('main');
    if (cible) cible.focus({ preventScroll: true });
  });
  function surDefilement() { haut.classList.toggle('est-visible', window.scrollY > 900); }
  window.addEventListener('scroll', surDefilement, { passive: true });
  surDefilement();

  /* ---------- Apparition douce des blocs au défilement ---------- */
  var aAnimer = document.querySelectorAll('.apparition');
  if ('IntersectionObserver' in window && !reduit) {
    var obs = new IntersectionObserver(function (entrees) {
      entrees.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('est-visible'); obs.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    aAnimer.forEach(function (el) { obs.observe(el); });
  } else {
    aAnimer.forEach(function (el) { el.classList.add('est-visible'); });
  }
})();
