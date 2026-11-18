/* Bloc « Sommaire de la page » : met en évidence la partie en cours de lecture. */
(function () {
  'use strict';
  var liens = document.querySelectorAll('.sommaire a');
  if (!liens.length || !('IntersectionObserver' in window)) return;
  var parId = {};
  liens.forEach(function (a) { parId[decodeURIComponent(a.getAttribute('href').slice(1))] = a; });
  var obs = new IntersectionObserver(function (entrees) {
    entrees.forEach(function (en) {
      if (!en.isIntersecting) return;
      liens.forEach(function (a) { a.classList.remove('est-courant'); a.removeAttribute('aria-current'); });
      var lien = parId[en.target.id];
      if (lien) { lien.classList.add('est-courant'); lien.setAttribute('aria-current', 'true'); }
    });
  }, { rootMargin: '-30% 0px -60% 0px' });
  Object.keys(parId).forEach(function (id) { var cible = document.getElementById(id); if (cible) obs.observe(cible); });
})();
